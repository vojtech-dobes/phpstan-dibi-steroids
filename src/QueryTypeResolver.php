<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use PHPSQLParser;
use PHPStan;


final class QueryTypeResolver
{

	public function __construct(
		private readonly DatabaseType $databaseType,
		private readonly Query\TypeProvider $defaultTypeProvider,
		private readonly PHPSQLParser\PHPSQLParser $sqlParser,
	) {}



	public function resolveQueryType(string $sqlString): ?PHPStan\Type\Type
	{
		return $this->resolveQuerySubtreeType(
			$this->defaultTypeProvider,
			$this->sqlParser->parse($sqlString),
		);
	}



	private function resolveQueryStringType(
		Query\TypeProvider $typeProvider,
		string $sqlString,
	): ?PHPStan\Type\Type
	{
		return $this->resolveQuerySubtreeType(
			$typeProvider,
			$this->sqlParser->parse($sqlString),
		);
	}



	/**
	 * @param array<mixed> $querySubtree
	 */
	private function resolveQuerySubtreeType(
		Query\TypeProvider $typeProvider,
		array $querySubtree,
	): ?PHPStan\Type\Type
	{
		if (
			isset($querySubtree['SELECT']) === false
			&& (count($querySubtree) > 1 || isset($querySubtree['VALUES']) === false)
		) {
			return null;
		}

		$typeProvider = $this->addCommonTableExpressionsToTypeProvider(
			$typeProvider,
			$querySubtree['WITH'] ?? [],
		);

		$dataSources = $this->collectDataSources(
			$typeProvider,
			$querySubtree['FROM'] ?? [],
		);

		$columnConstraints = $this->processWhereClauses(
			new Query\Context(
				columnConstraints: [],
				dataSources: $dataSources,
				typeProvider: $typeProvider,
			),
			$querySubtree['WHERE'] ?? [],
		);

		$selections = iterator_to_array(
			$this->collectSelections(
				new Query\Context(
					columnConstraints: $columnConstraints,
					dataSources: $dataSources,
					typeProvider: $typeProvider,
				),
				$querySubtree['SELECT'] ?? [],
				$querySubtree['VALUES'] ?? [],
			),
		);

		if ($selections === []) {
			return null;
		}

		return $this->createRowType(
			$selections,
			hasFrom: isset($querySubtree['FROM']),
			isGrouped: isset($querySubtree['GROUP']),
		);
	}



	/**
	 * @param list<array<mixed>> $subtrees
	 */
	private function addCommonTableExpressionsToTypeProvider(
		Query\TypeProvider $typeProvider,
		array $subtrees,
	): Query\TypeProvider
	{
		foreach ($subtrees as $subtree) {
			$name = $subtree['sub_tree'][0]['name'] ?? null;

			if ($name === null) {
				continue;
			}

			$rowType = PHPStan\Type\TypeCombinator::removeNull(
				$this->resolveQuerySubtreeType(
					$typeProvider,
					$subtree['sub_tree'][2]['sub_tree'],
				) ?? new PHPStan\Type\MixedType(),
			);

			$typeProvider = $typeProvider->addVirtualTable(
				Helpers::normalizeName($name),
				$rowType,
			);
		}

		return $typeProvider;
	}



	/**
	 * @param list<array<mixed>> $subtrees
	 * @return array<string, Query\DataSource>
	 */
	private function collectDataSources(
		Query\TypeProvider $typeProvider,
		array $subtrees,
	): array
	{
		$dataSources = [];

		foreach ($subtrees as $subtree) {
			$name = $subtree['no_quotes']['parts'][0] ?? '';

			if ($subtree['expr_type'] === 'subquery') {
				$selectionProvider = new Query\SubquerySelectionProvider(
					PHPStan\Type\TypeCombinator::removeNull(
						$this->resolveQuerySubtreeType(
							$typeProvider,
							$subtree['sub_tree'],
						) ?? new PHPStan\Type\MixedType(),
					),
				);
			} elseif ($subtree['expr_type'] === 'table' && $subtree['table'] !== '') {
				$normalizedName = Helpers::normalizeName($name);

				if ($typeProvider->hasView($normalizedName)) {
					$selectionProvider = new Query\SubquerySelectionProvider(
						PHPStan\Type\TypeCombinator::removeNull(
							$this->resolveQueryType(
								$typeProvider->getViewDefinition($normalizedName),
							) ?? new PHPStan\Type\MixedType(),
						),
					);
				} else {
					$selectionProvider = new Query\TableSelectionProvider(
						$typeProvider,
						$name,
					);
				}
			} elseif ($subtree['expr_type'] === 'table_expression') {
				if ($this->databaseType->isTableExpressionAllowedAsDataSource() === false) {
					continue;
				}

				$selectionProvider = new Query\SubquerySelectionProvider(
					PHPStan\Type\TypeCombinator::removeNull(
						$this->resolveQueryStringType(
							$typeProvider,
							$subtree['base_expr'],
						) ?? new PHPStan\Type\MixedType(),
					),
				);
			} else {
				continue;
			}

			$dataSources[] = new Query\DataSource(
				alias: $subtree['alias'] !== false
					? $subtree['alias']['no_quotes']['parts'][0]
					: $name,
				joinType: Query\JoinType::from($subtree['join_type']),
				name: $name,
				selectionProvider: $selectionProvider,
			);
		}

		return array_combine(
			array_column($dataSources, 'alias'),
			$dataSources,
		);
	}



	/**
	 * @param list<array<mixed>> $selectSubtrees
	 * @param list<array<mixed>> $valuesSubtrees
	 * @return iterable<string, Query\Selection>
	 */
	private function collectSelections(
		Query\Context $context,
		array $selectSubtrees,
		array $valuesSubtrees,
	): iterable
	{
		foreach ($selectSubtrees as $subtree) {
			$subtree = $this->fixBooleanConstRecognition($subtree);

			match (true) {
				$subtree === ['delim' => false],
				$subtree['expr_type'] === 'comment',
				$subtree['expr_type'] === 'reserved' => yield from [],
				$subtree['expr_type'] === 'aggregate_function',
				$subtree['expr_type'] === 'bracket_expression',
				$subtree['expr_type'] === 'const',
				$subtree['expr_type'] === 'expression',
				$subtree['expr_type'] === 'function' => yield from $this->createSelectionFromNonColumn($context, $subtree),
				$subtree['expr_type'] === 'colref' => ($subtree['base_expr'] === '*' || in_array('*', $subtree['no_quotes']['parts'], true))
					? yield from $this->createSelectionsFromWildcardColumn($context, $subtree)
					: yield from $this->createSelectionFromSingleColumn($context, $subtree),
				$subtree['base_expr'] === 'SQL_CACHE' => yield from [],
				default => throw new PHPStan\ShouldNotHappenException(),
			};
		}

		if ($valuesSubtrees !== []) {
			$columnNames = [];
			$valueTypes = [];

			foreach ($valuesSubtrees as $subtree) {
				if (
					$subtree['expr_type'] !== 'record'
					|| $subtree['data'] === []
				) {
					continue;
				}

				if ($columnNames !== [] && count($columnNames) !== count($subtree['data'])) {
					$valueTypes = array_map(
						static fn () => new PHPStan\Type\ErrorType(),
						$valueTypes,
					);

					break;
				}

				foreach ($subtree['data'] as $i => $dataSubtree) {
					$columnNames[$i] ??= $this->databaseType->getValuesRecordColumnName($i, $dataSubtree);

					$columnName = $columnNames[$i];

					$valueType = $this->getSubtreeType($context, $dataSubtree);

					if ($dataSubtree['expr_type'] === 'subquery') {
						$valueType = $valueType->getFirstIterableValueType();
					}

					if (isset($valueTypes[$columnName])) {
						$valueType = PHPStan\Type\TypeCombinator::union(
							$valueTypes[$columnName],
							$valueType,
						);
					}

					$valueTypes[$columnName] = $valueType;
				}
			}

			yield from array_map(
				static fn ($valueType) => new Query\Selection(
					isAggregation: false,
					type: $valueType,
				),
				$valueTypes,
			);
		}
	}



	/**
	 * @param array<mixed> $subtree
	 * @return iterable<string, Query\Selection>
	 */
	private function createSelectionFromNonColumn(
		Query\Context $context,
		array $subtree,
	): iterable
	{
		yield $this->buildAliasFromSubtree($subtree) => new Query\Selection(
			isAggregation: $subtree['expr_type'] === 'aggregate_function',
			type: $this->getSubtreeType($context, $subtree),
		);
	}



	/**
	 * @param array<mixed> $subtree
	 * @return iterable<string, Query\Selection>
	 */
	private function createSelectionFromSingleColumn(
		Query\Context $context,
		array $subtree,
	): iterable
	{
		$referencesTable = count($subtree['no_quotes']['parts']) > 1;

		$alias = Helpers::normalizeName(
			$subtree['alias'] !== false
				? $subtree['alias']['no_quotes']['parts'][0]
				: $subtree['no_quotes']['parts'][$referencesTable ? 1 : 0],
		);

		yield $alias => new Query\Selection(
			isAggregation: false,
			type: $this->getSubtreeType($context, $subtree),
		);
	}



	/**
	 * @param array<mixed> $subtree
	 * @return iterable<string, Query\Selection>
	 */
	private function createSelectionsFromWildcardColumn(
		Query\Context $context,
		array $subtree,
	): iterable
	{
		yield from $context->getWildcardSelections(
			tableName: $subtree['base_expr'] === '*'
				? null
				: reset($subtree['no_quotes']['parts']),
		);
	}



	/**
	 * @param array<Query\Selection> $selections
	 */
	private function createRowType(
		array $selections,
		bool $hasFrom,
		bool $isGrouped,
	): PHPStan\Type\Type
	{
		$result = PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty();

		foreach ($selections as $key => $selection) {
			$result->setOffsetValueType(
				is_int($key)
					? new PHPStan\Type\Constant\ConstantIntegerType($key)
					: new PHPStan\Type\Constant\ConstantStringType($key),
				$selection->type,
			);
		}

		$result = $result->getArray();

		$alwaysReturns = $hasFrom === false || ($isGrouped === false && (function_exists('array_any') ? array_any(
			$selections,
			static fn ($selection) => $selection->isAggregation,
		) : (array_filter(
			$selections,
			static fn ($selection) => $selection->isAggregation,
		) !== [])));

		if ($alwaysReturns === false) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result;
	}



	/**
	 * @param array<mixed> $subtree
	 */
	public function getSubtreeType(
		Query\Context $context,
		array $subtree,
	): PHPStan\Type\Type
	{
		$subtree = $this->fixBooleanConstRecognition($subtree);

		$result = $this->resolveSubtreeType($context, $subtree);

		return PHPStan\Type\TypeTraverser::map(
			$result,
			function (PHPStan\Type\Type $type, callable $traverse): PHPStan\Type\Type {
				if (
					$type->getConstantScalarValues() === [TranslateSimulator::Float]
					|| $type->getConstantScalarValues() === [TranslateSimulator::Integer]
					|| $type->getConstantScalarValues() === [TranslateSimulator::String]
				) {
					return $type->generalize(PHPStan\Type\GeneralizePrecision::lessSpecific());
				}

				if ($type->isBoolean()->yes() && $this->databaseType->supportsNativeBoolean() === false) {
					return PHPStan\Type\TypeCombinator::union(
						...array_map(
							static fn ($scalarValue) => match ($scalarValue) {
								true => new PHPStan\Type\Constant\ConstantIntegerType(1),
								false => new PHPStan\Type\Constant\ConstantIntegerType(0),
								default => throw new PHPStan\ShouldNotHappenException(),
							},
							$type->getConstantScalarValues(),
						),
					);
				}

				return $traverse($type);
			},
		);
	}



	/**
	 * @param array<mixed> $subtree
	 */
	private function resolveSubtreeType(
		Query\Context $context,
		array $subtree,
	): PHPStan\Type\Type
	{
		return match ($subtree['expr_type']) {
			'aggregate_function', 'function' => $this->getFunctionSubtreeType($context, $subtree),
			'bracket_expression', 'expression' => $this->getExpressionSubtreeType($context, $subtree),
			'colref' => $this->getColrefSubtreeType($context, $subtree),
			'const' => $this->getConstSubtreeType($subtree),
			'in-list' => $this->getInListSubtreeType($context, $subtree),
			'subquery' => $this->resolveQuerySubtreeType($context->typeProvider, $subtree['sub_tree']) ?? new PHPStan\Type\ErrorType(),
			default => new PHPStan\Type\MixedType(),
		};
	}



	/**
	 * @param array<mixed> $subtree
	 */
	private function getColrefSubtreeType(
		Query\Context $context,
		array $subtree,
	): PHPStan\Type\Type
	{
		$referencesTable = count($subtree['no_quotes']['parts']) > 1;

		return $context->getColumnType(
			tableName: $referencesTable ? $subtree['no_quotes']['parts'][0] : null,
			columnName: $subtree['no_quotes']['parts'][$referencesTable ? 1 : 0],
		);
	}



	/**
	 * @param array<mixed> $subtree
	 */
	private function getConstSubtreeType(array $subtree): PHPStan\Type\Type
	{
		/** @var string $value */
		$value = $subtree['base_expr'];

		$builtinConst = match (strtolower($value)) {
			'false' => new PHPStan\Type\Constant\ConstantBooleanType(false),
			'null' => new PHPStan\Type\NullType(),
			'true' => new PHPStan\Type\Constant\ConstantBooleanType(true),
			default => null,
		};

		if ($builtinConst !== null) {
			return $builtinConst;
		}

		if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
			return new PHPStan\Type\Constant\ConstantStringType(
				substr($value, 1, -1),
			);
		}

		if (is_numeric($value)) {
			return str_contains($value, '.')
				? new PHPStan\Type\Constant\ConstantFloatType((float) $value)
				: new PHPStan\Type\Constant\ConstantIntegerType((int) $value);
		}

		return new PHPStan\Type\ErrorType();
	}



	/**
	 * @param array<mixed> $subtree
	 */
	private function getExpressionSubtreeType(
		Query\Context $context,
		array $subtree,
	): PHPStan\Type\Type
	{
		if (count($subtree['sub_tree']) === 1) {
			return $this->getSubtreeType(
				$context,
				$subtree['sub_tree'][0],
			);
		}

		if (count($subtree['sub_tree']) === 3) {
			$potentialBinaryOperator = $subtree['sub_tree'][1]['base_expr'];

			if (in_array($potentialBinaryOperator, [
				'+',
				'-',
				'*',
			], true)) {
				return PHPStan\Type\TypeCombinator::union(
					$this->getSubtreeType($context, $subtree['sub_tree'][0]),
					$this->getSubtreeType($context, $subtree['sub_tree'][2]),
				);
			}

			if (in_array($potentialBinaryOperator, [
				'/',
			], true)) {
				return new PHPStan\Type\FloatType();
			}

			if (in_array($potentialBinaryOperator, [
				'=',
			], true)) {
				$typeA = $this->getSubtreeType($context, $subtree['sub_tree'][0]);
				$typeB = $this->getSubtreeType($context, $subtree['sub_tree'][2]);

				if ($typeA->isNull()->yes() || $typeB->isNull()->yes()) {
					return new PHPStan\Type\NullType();
				}

				$typeAScalarValues = $typeA->getConstantScalarValues();
				$typeBScalarValues = $typeB->getConstantScalarValues();

				if (count($typeAScalarValues) === 1 && count($typeBScalarValues) === 1) {
					return new PHPStan\Type\Constant\ConstantBooleanType(
						$typeAScalarValues === $typeBScalarValues,
					);
				}

				$result = new PHPStan\Type\BooleanType();

				if ($typeA->isNull()->no() === false || $typeB->isNull()->no() === false) {
					$result = PHPStan\Type\TypeCombinator::addNull($result);
				}

				return $result;
			}

			if (in_array($potentialBinaryOperator, [
				'IS',
			], true)) {
				$typeA = $this->getSubtreeType($context, $subtree['sub_tree'][0]);
				$typeB = $this->getSubtreeType($context, $subtree['sub_tree'][2]);

				$typeAScalarValues = $typeA->getConstantScalarValues();
				$typeBScalarValues = $typeB->getConstantScalarValues();

				if (count($typeAScalarValues) === 1 && count($typeBScalarValues) === 1) {
					return new PHPStan\Type\Constant\ConstantBooleanType(
						$typeAScalarValues === $typeBScalarValues,
					);
				}

				return new PHPStan\Type\BooleanType();
			}

			if (in_array($potentialBinaryOperator, [
				'!=',
			], true)) {
				$typeA = $this->getSubtreeType($context, $subtree['sub_tree'][0]);
				$typeB = $this->getSubtreeType($context, $subtree['sub_tree'][2]);

				if ($typeA->isNull()->yes() || $typeB->isNull()->yes()) {
					return new PHPStan\Type\NullType();
				}

				$typeAScalarValues = $typeA->getConstantScalarValues();
				$typeBScalarValues = $typeB->getConstantScalarValues();

				if (count($typeAScalarValues) === 1 && count($typeBScalarValues) === 1) {
					$result = new PHPStan\Type\Constant\ConstantBooleanType(
						$typeAScalarValues !== $typeBScalarValues,
					);
				} else {
					$result = new PHPStan\Type\BooleanType();
				}

				if ($typeA->isNull()->no() === false || $typeB->isNull()->no() === false) {
					$result = PHPStan\Type\TypeCombinator::addNull($result);
				}

				return $result;
			}
		}

		if (count($subtree['sub_tree']) === 4) {
			$potentialBinaryOperator = $subtree['sub_tree'][1]['base_expr'];

			if (
				$subtree['sub_tree'][1]['base_expr'] === 'IS'
				&& $subtree['sub_tree'][2]['base_expr'] === 'NOT'
			) {
				$typeA = $this->getSubtreeType($context, $subtree['sub_tree'][0]);
				$typeB = $this->getSubtreeType($context, $subtree['sub_tree'][3]);

				$typeAScalarValues = $typeA->getConstantScalarValues();
				$typeBScalarValues = $typeB->getConstantScalarValues();

				if (count($typeAScalarValues) === 1 && count($typeBScalarValues) === 1) {
					return new PHPStan\Type\Constant\ConstantBooleanType(
						$typeAScalarValues !== $typeBScalarValues,
					);
				}

				return new PHPStan\Type\BooleanType();
			}
		}

		return new PHPStan\Type\MixedType();
	}



	/**
	 * @param array<mixed> $subtree
	 */
	private function getFunctionSubtreeType(
		Query\Context $context,
		array $subtree,
	): PHPStan\Type\Type
	{
		$function = $this->getFunctionImplementation(
			strtolower($subtree['base_expr']),
		);

		if ($function === null) {
			return new PHPStan\Type\MixedType();
		}

		$result = $function->getReturnType(
			$this->databaseType,
			new Functions\SubtreeResolver($context, $this),
			$subtree,
		);

		if ($result === null) {
			return new PHPStan\Type\MixedType();
		}

		return $result;
	}



	/**
	 * @param array<mixed> $subtree
	 */
	private function getInListSubtreeType(
		Query\Context $context,
		array $subtree,
	): PHPStan\Type\Type
	{
		return PHPStan\Type\TypeCombinator::union(
			...array_map(
				fn ($item) => $this->getSubtreeType($context, $item),
				$subtree['sub_tree'],
			),
		);
	}



	private function getFunctionImplementation(string $functionName): ?Functions\FunctionInterface
	{
		return match ($functionName) {
			'abs' => new Functions\Abs(),
			'avg' => new Functions\Avg(),
			'changes' => new Functions\Changes(),
			'char' => new Functions\Char(),
			'coalesce' => new Functions\Coalesce(),
			'concat' => new Functions\Concat(),
			'concat_ws' => new Functions\ConcatWs(),
			'count' => new Functions\Count(),
			'format' => new Functions\Format(),
			'glob' => new Functions\Glob(),
			'group_concat' => new Functions\GroupConcat(),
			'hex' => new Functions\Hex(),
			'if' => new Functions\IfFunction(),
			'ifnull' => new Functions\Ifnull(),
			'iif' => new Functions\Iif(),
			'instr' => new Functions\Instr(),
			'last_insert_rowid' => new Functions\LastInsertRowid(),
			'length' => new Functions\Length(),
			'like' => new Functions\Like(),
			'likelihood' => new Functions\Likelihood(),
			'likely' => new Functions\Likely(),
			'load_extension' => new Functions\LoadExtension(),
			'lower' => new Functions\Lower(),
			'ltrim' => new Functions\Ltrim(),
			'max' => new Functions\Max(),
			'min' => new Functions\Min(),
			'nullif' => new Functions\Nullif(),
			'octet_length' => new Functions\OctetLength(),
			'printf' => new Functions\Printf(),
			'quote' => new Functions\Quote(),
			'random' => new Functions\Random(),
			'randomblob' => new Functions\Randomblob(),
			'replace' => new Functions\Replace(),
			'round' => new Functions\Round(),
			'rtrim' => new Functions\Rtrim(),
			'sign' => new Functions\Sign(),
			'soundex' => new Functions\Soundex(),
			'sqlite_compileoption_get' => new Functions\SqliteCompileoptionGet(),
			'sqlite_compileoption_used' => new Functions\SqliteCompileoptionUsed(),
			'sqlite_offset' => new Functions\SqliteOffset(),
			'sqlite_source_id' => new Functions\SqliteSourceId(),
			'sqlite_version' => new Functions\SqliteVersion(),
			'string_agg' => new Functions\StringAgg(),
			'substr' => new Functions\Substr(),
			'substring' => new Functions\Substring(),
			'sum' => new Functions\Sum(),
			'total' => new Functions\Total(),
			'total_changes' => new Functions\TotalChanges(),
			'trim' => new Functions\Trim(),
			'typeof' => new Functions\Typeof(),
			'unhex' => new Functions\Unhex(),
			'unicode' => new Functions\Unicode(),
			'unistr' => new Functions\Unistr(),
			'unistr_quote' => new Functions\UnistrQuote(),
			'unlikely' => new Functions\Unlikely(),
			'upper' => new Functions\Upper(),
			'zeroblob' => new Functions\Zeroblob(),
			default => null,
		};
	}



	/**
	 * @param array<mixed> $subtree
	 * @throws PHPStan\ShouldNotHappenException
	 */
	private function buildAliasFromSubtree(array $subtree): string
	{
		if (isset($subtree['alias']) && $subtree['alias'] !== false) {
			return Helpers::normalizeName($subtree['alias']['no_quotes']['parts'][0]);
		}

		if ($subtree['expr_type'] === 'colref') {
			return $subtree['base_expr'];
		}

		if (
			$subtree['expr_type'] === 'aggregate_function'
			|| $subtree['expr_type'] === 'function'
		) {
			$function = $this->getFunctionImplementation(
				strtolower($subtree['base_expr']),
			);

			if ($function !== null && $function instanceof Functions\FunctionInterfaceName) {
				$result = $function->getName(
					$this->databaseType,
					$subtree,
				);

				if ($result !== null) {
					return $result;
				}
			}
		}

		$result = $this->databaseType->getColumnNameForSubtree(
			$subtree,
			$this->buildNaturalAliasFromSubtree(...),
		);

		if ($result !== null) {
			return $result;
		}

		throw new PHPStan\ShouldNotHappenException(
			"Unsupported SQL expression type '{$subtree['expr_type']}'",
		);
	}



	/**
	 * @param array<mixed> $subtree
	 * @throws PHPStan\ShouldNotHappenException
	 */
	private function buildNaturalAliasFromSubtree(array $subtree): string
	{
		return match ($subtree['expr_type']) {
			'aggregate_function' => implode(', ', array_map($this->buildNaturalAliasFromSubtree(...), $subtree['sub_tree'])),
			'bracket_expression',
			'expression' => implode(' ', array_map($this->buildNaturalAliasFromSubtree(...), $subtree['sub_tree'])),
			'colref',
			'const',
			'reserved' => $subtree['base_expr'],
			default => new PHPStan\ShouldNotHappenException(
				"Unsupported SQL expression type '{$subtree['expr_type']}'",
			),
		};
	}



	/**
	 * @param list<array<mixed>> $whereClauses
	 * @return array<string, PHPStan\Type\Type>
	 */
	public function processWhereClauses(
		Query\Context $context,
		array $whereClauses,
	): array
	{
		return $this->extractConstraintsFromOr(
			$context,
			$this->groupBooleanExpression($whereClauses)['sub_tree'],
		);
	}



	/**
	 * @param list<array<mixed>> $subtrees
	 * @return array<mixed>
	 */
	private function groupBooleanExpression(
		array $subtrees,
	): array
	{
		$orClauses = [];
		$andClauses = [];

		$clause = [];

		foreach ($subtrees as $subtree) {
			if ($subtree['expr_type'] === 'bracket_expression') {
				$clause[] = $this->groupBooleanExpression($subtree['sub_tree']);
			} elseif ($subtree['expr_type'] === 'operator') {
				$operator = strtolower($subtree['base_expr']);

				if ($operator === 'and') {
					$andClauses[] = [
						'expr_type' => 'bracket_expression',
						'sub_tree' => $clause,
					];

					$clause = [];
				} elseif ($operator === 'or') {
					$orClauses[] = [
						'expr_type' => 'pseudo_and',
						'sub_tree' => $andClauses,
					];

					$andClauses = [];
				} else {
					$clause[] = $subtree;
				}
			} else {
				$clause[] = $subtree;
			}
		}

		if ($clause !== []) {
			$andClauses[] = [
				'expr_type' => 'bracket_expression',
				'sub_tree' => $clause,
			];
		}

		if ($andClauses !== []) {
			$orClauses[] = [
				'expr_type' => 'pseudo_and',
				'sub_tree' => $andClauses,
			];
		}

		return [
			'expr_type' => 'pseudo_or',
			'sub_tree' => $orClauses,
		];
	}



	/**
	 * @param array<mixed> $subtree
	 * @return array<string, PHPStan\Type\Type>
	 */
	private function extractConstraintsFromSubtree(
		Query\Context $context,
		array $subtree,
	): array
	{
		return match ($subtree['expr_type']) {
			'bracket_expression' => $this->extractConstraintsFromBracketExpression($context, $subtree['sub_tree']),
			'pseudo_and' => $this->extractConstraintsFromAnd($context, $subtree['sub_tree']),
			'pseudo_or' => $this->extractConstraintsFromOr($context, $subtree['sub_tree']),
			default => [],
		};
	}



	/**
	 * @param list<array<mixed>> $subtrees
	 * @return array<string, PHPStan\Type\Type>
	 */
	private function extractConstraintsFromOr(
		Query\Context $context,
		array $subtrees,
	): array
	{
		$result = [];

		foreach ($subtrees as $subtree) {
			$columnConstraints = $this->extractConstraintsFromSubtree($context, $subtree);

			foreach ($columnConstraints as $columnName => $constraintType) {
				if (array_key_exists($columnName, $result)) {
					$result[$columnName] = PHPStan\Type\TypeCombinator::union(
						$result[$columnName],
						$constraintType,
					);
				} else {
					$result[$columnName] = $constraintType;
				}
			}
		}

		return $result;
	}



	/**
	 * @param list<array<mixed>> $subtrees
	 * @return array<string, PHPStan\Type\Type>
	 */
	private function extractConstraintsFromAnd(
		Query\Context $context,
		array $subtrees,
	): array
	{
		$result = [];

		foreach ($subtrees as $subtree) {
			$columnConstraints = $this->extractConstraintsFromSubtree($context, $subtree);

			foreach ($columnConstraints as $columnName => $constraintType) {
				if (array_key_exists($columnName, $result)) {
					$result[$columnName] = PHPStan\Type\TypeCombinator::intersect(
						$result[$columnName],
						$constraintType,
					);
				} else {
					$result[$columnName] = $constraintType;
				}
			}
		}

		return $result;
	}



	/**
	 * @param list<array<mixed>> $subtrees
	 * @return array<string, PHPStan\Type\Type>
	 */
	private function extractConstraintsFromBracketExpression(
		Query\Context $context,
		array $subtrees,
	): array
	{
		if (count($subtrees) === 1) {
			return $this->extractConstraintsFromSubtree($context, $subtrees[0]);
		}

		if (count($subtrees) === 3) {
			$columnLeft = $subtrees[0]['expr_type'] === 'colref'
				? $subtrees[0]['no_quotes']['parts'][0]
				: null;

			$columnRight = $subtrees[2]['expr_type'] === 'colref'
				? $subtrees[2]['no_quotes']['parts'][0]
				: null;

			if (($columnLeft !== null xor $columnRight !== null) === false) {
				return [];
			}

			$column = Helpers::normalizeName($columnLeft ?? $columnRight);

			$constraintType = $columnLeft !== null
				? $this->getSubtreeType($context, $subtrees[2])
				: $this->getSubtreeType($context, $subtrees[0]);

			$operator = strtolower($subtrees[1]['base_expr']);

			if ($operator === '=') {
				if ($constraintType->isNull()->yes()) {
					$constraintType = new PHPStan\Type\NeverType();
				}
			} elseif ($operator === '!=') {
				if ($constraintType->isNull()->yes()) {
					$constraintType = new PHPStan\Type\NeverType();
				} else {
					$constraintType = PHPStan\Type\TypeCombinator::remove(new PHPStan\Type\MixedType(), $constraintType);
				}
			} elseif (in_array($operator, [
				'<',
				'<=',
				'>',
				'>=',
			], true)) {
				$constraintType = new PHPStan\Type\MixedType();
			}

			return [$column => $constraintType];
		}

		if (
			count($subtrees) === 4
			&& (
				(
					strtolower($subtrees[1]['base_expr']) === 'is'
					&& strtolower($subtrees[2]['base_expr']) === 'not'
				)
				|| (
					strtolower($subtrees[1]['base_expr']) === 'not'
					&& strtolower($subtrees[2]['base_expr']) === 'in'
				)
			)
		) {
			$columnLeft = $subtrees[0]['expr_type'] === 'colref'
				? $subtrees[0]['no_quotes']['parts'][0]
				: null;

			$columnRight = $subtrees[3]['expr_type'] === 'colref'
				? $subtrees[3]['no_quotes']['parts'][0]
				: null;

			if (($columnLeft !== null xor $columnRight !== null) === false) {
				return [];
			}

			$column = Helpers::normalizeName($columnLeft ?? $columnRight);

			$constraintType = $columnLeft !== null
				? $this->getSubtreeType($context, $subtrees[3])
				: $this->getSubtreeType($context, $subtrees[0]);

			$constraintType = PHPStan\Type\TypeCombinator::remove(new PHPStan\Type\MixedType(), $constraintType);

			return [$column => $constraintType];
		}

		return [];
	}



	/**
	 * @param array<mixed> $subtree
	 * @return array<mixed>
	 */
	private function fixBooleanConstRecognition(array $subtree): array
	{
		if (
			isset($subtree['expr_type'])
			&& $subtree['expr_type'] === 'colref'
			&& in_array(strtolower($subtree['base_expr']), ['false', 'true'], true)
		) {
			$subtree['expr_type'] = 'const';
		}

		return $subtree;
	}

}
