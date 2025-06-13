<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use DateInterval;
use DateTime;
use PHPStan;


final class SchemaTypeResolver
{

	/**
	 * @param array<mixed> $columnData
	 */
	public static function resolveNativeTypeFromReflector(
		Database $database,
		string $columnName,
		string $type,
		array $columnData,
	): PHPStan\Type\Type
	{
		$typeDefinition = null;

		if ($database->type === DatabaseType::Mariadb || $database->type === DatabaseType::Mysql) {
			$columnType = $database->connection->query('
				SELECT `COLUMN_TYPE`
				FROM `INFORMATION_SCHEMA`.`COLUMNS`
				WHERE
					`TABLE_SCHEMA` = DATABASE()
					AND `TABLE_NAME` = %s', $columnData['table'], '
					AND `COLUMN_NAME` = %s', $columnData['name'], '
			')->fetchSingle();

			$checkClause = $database->connection->query('
				SELECT `CHECK_CLAUSE`
				FROM `INFORMATION_SCHEMA`.`CHECK_CONSTRAINTS`
				WHERE
					`CONSTRAINT_SCHEMA` = DATABASE()
					AND `TABLE_NAME` = %s', $columnData['table'], '
					AND `CONSTRAINT_NAME` = %s', $columnData['name'], '
			')->fetchSingle() ?? '';

			if ($checkClause !== '') {
				$checkClause = 'CHECK (' . $checkClause . ')';
			}

			$tableDefinition = $database->sqlParser->parse("CREATE TABLE `foo` (`{$columnData['name']}` {$columnType} {$checkClause})");
			$typeDefinition = $tableDefinition['TABLE']['create-def']['sub_tree'][0]['sub_tree'][1];
		} elseif ($database->type === DatabaseType::Postgres) {
			$columnType = $database->connection->query('
				SELECT format_type(a.atttypid, a.atttypmod) AS full_type
				FROM
					pg_attribute a
				JOIN pg_class t ON a.attrelid = t.oid
				JOIN pg_namespace n ON t.relnamespace = n.oid
				WHERE
					a.attnum > 0
					AND NOT a.attisdropped
					AND t.relkind = %s', 'r', '
					AND n.nspname = current_schema
					AND t.relname = %s', $columnData['table'], '
					AND a.attname = %s', $columnData['name'], '
			')->fetchSingle();

			$tableDefinition = $database->sqlParser->parse("CREATE TABLE \"foo\" (\"{$columnData['name']}\" {$columnType})");
			$typeDefinition = $tableDefinition['TABLE']['create-def']['sub_tree'][0]['sub_tree'][1];

			if (str_starts_with($type, '_')) {
				$type = $columnType;
			}
		} elseif ($database->type === DatabaseType::Sqlite) {
			$sql = $database->connection->query('
				SELECT `sql`
				FROM `sqlite_master`
				WHERE
					`tbl_name` = %s', $columnData['table'], '
					AND `name` = %s', $columnData['name'], '
			')->fetchSingle();

			$tableDefinition = $database->sqlParser->parse($sql);

			foreach ($tableDefinition['TABLE']['create-def']['sub_tree'] as $item) {
				if ($item['expr_type'] === 'column-def' && Helpers::normalizeName($item['sub_tree'][0]['base_expr']) === $columnData['name']) {
					$typeDefinition = $item['sub_tree'][1];
				}
			}
		}

		return self::resolveNativeType(
			$database,
			$columnName,
			$type,
			$typeDefinition,
		);
	}



	/**
	 * @param array<mixed>|null $typeDefinition
	 */
	public static function resolveNativeType(
		Database $database,
		string $columnName,
		string $type,
		?array $typeDefinition = null,
	): PHPStan\Type\Type
	{
		return match ($database->type) {
			DatabaseType::Mariadb => self::resolveNativeMariadbType($database, $columnName, $type, $typeDefinition),
			DatabaseType::Mysql => self::resolveNativeMariadbType($database, $columnName, $type, $typeDefinition),
			DatabaseType::Postgres => self::resolveNativePostgresType($database, $columnName, $type, $typeDefinition),
			DatabaseType::Sqlite => self::resolveNativeSqliteType($database, $columnName, $type, $typeDefinition),
		};
	}



	/**
	 * @param array<mixed>|null $typeDefinition
	 */
	private static function resolveNativeMariadbType(
		Database $database,
		string $columnName,
		string $type,
		?array $typeDefinition,
	): PHPStan\Type\Type
	{
		$result = new PHPStan\Type\MixedType();

		$parts = explode(' ', $type);

		$unsigned = (
			$typeDefinition !== null
			&& in_array(
				'unsigned',
				array_map('strtolower', array_column($typeDefinition['sub_tree'], 'base_expr')),
				true,
			)
		);

		foreach ($parts as $part) {
			$type = match ($part) {
				// boolean
				'bool',
				'boolean' => new PHPStan\Type\UnionType([
					new PHPStan\Type\Constant\ConstantIntegerType(0),
					new PHPStan\Type\Constant\ConstantIntegerType(1),
				]),

				// float
				'float',
				'double',
				'decimal',
				'numeric' => new PHPStan\Type\FloatType(),

				// int
				'tinyint' => PHPStan\Type\IntegerRangeType::fromInterval($unsigned ? 0 : -128, $unsigned ? 255 : 127),
				'smallint' => PHPStan\Type\IntegerRangeType::fromInterval($unsigned ? 0 : -32768, $unsigned ? 65535 : 32767),
				'mediumint' => PHPStan\Type\IntegerRangeType::fromInterval($unsigned ? 0 : -8388608, $unsigned ? 16777215 : 8388607),
				'int', 'integer' => PHPStan\Type\IntegerRangeType::fromInterval($unsigned ? 0 : -2147483648, $unsigned ? 4294967295 : 2147483647),
				'bigint' => PHPStan\Type\IntegerRangeType::fromInterval(
					$unsigned ? 0 : null,
					null,
				),

				// year
				'year' => new PHPStan\Type\UnionType([
					new PHPStan\Type\Constant\ConstantIntegerType(0),
					PHPStan\Type\IntegerRangeType::fromInterval(1, 69),
					PHPStan\Type\IntegerRangeType::fromInterval(70, 99),
					PHPStan\Type\IntegerRangeType::fromInterval(1901, 2155),
				]),

				// binary integer
				'bit' => new PHPStan\Type\IntegerType(),

				// datetime
				'date',
				'time',
				'datetime',
				'timestamp' => new PHPStan\Type\ObjectType(DateTime::class),

				// string
				'char',
				'varchar',
				'binary',
				'varbinary',
				'tinyblob',
				'tinytext',
				'text',
				'blob',
				'mediumtext',
				'mediumblob',
				'longtext',
				'longblob',
				'set',
				'json' => new PHPStan\Type\StringType(),

				'enum' => ($typeDefinition !== null && $typeDefinition['sub_tree'][0]['sub_tree']['expr_type'] === 'bracket_expression')
					? PHPStan\Type\TypeCombinator::union(
						...array_map(
							static fn ($item) => $database
								->createQueryTypeResolver(null)
								->getSubtreeType(new Query\Context([], [], new Query\TypeProvider(null)), $item),
							$typeDefinition['sub_tree'][0]['sub_tree']['sub_tree'],
						),
					)
					: new PHPStan\Type\StringType(),

				default => null,
			};

			if ($type !== null) {
				$result = $type;

				break;
			}
		}

		if ($typeDefinition !== null) {
			foreach ($typeDefinition['sub_tree'] as $subtree) {
				if ($subtree['expr_type'] === 'check') {
					$checkConstraints = $database->createQueryTypeResolver(null)->processWhereClauses(
						new Query\Context([], [], new Query\TypeProvider(null)),
						$subtree['sub_tree']['sub_tree'],
					);

					if (isset($checkConstraints[$columnName])) {
						$result = PHPStan\Type\TypeCombinator::intersect($result, $checkConstraints[$columnName]);
					}
				}
			}
		}

		return $result;
	}



	/**
	 * @param array<mixed>|null $typeDefinition
	 */
	private static function resolveNativePostgresType(
		Database $database,
		string $columnName,
		string $type,
		?array $typeDefinition,
	): PHPStan\Type\Type
	{
		$result = new PHPStan\Type\MixedType();

		$parts = explode(' ', $type);

		$lowerType = strtolower($type);
		$isArray = str_ends_with($lowerType, '[]');
		$baseType = $isArray ? rtrim($lowerType, '[]') : $lowerType;

		$origType = $type;
		$type = match ($baseType) {
			// Boolean
			'bool',
			'boolean' => new PHPStan\Type\BooleanType(),

			// integer
			'smallint',
			'bigint',
			'integer',
			'int',
			'int2',
			'int4',
			'int8',
			'serial',
			'serial4',
			'serial8',
			'bigserial' => new PHPStan\Type\IntegerType(),

			// float
			'decimal',
			'numeric',
			'real',
			'float4',
			'float8',
			'double',
			'money' => new PHPStan\Type\FloatType(),

			// string
			'bpchar',
			'char',
			'character',
			'varchar',
			'text',
			'name',
			'uuid',
			'bytea' => new PHPStan\Type\StringType(),

			// datetime
			'date',
			'time',
			'timestamp',
			'timestamptz',
			'timetz' => new PHPStan\Type\ObjectType(DateTime::class),

			'interval' => new PHPStan\Type\ObjectType(DateInterval::class),

			// bits
			'bit',
			'varbit' => new PHPStan\Type\StringType(),

			// network
			'cidr',
			'inet',
			'macaddr',
			'macaddr8' => new PHPStan\Type\StringType(),

			// json
			'json',
			'jsonb' => new PHPStan\Type\StringType(),

			// XML
			'xml' => new PHPStan\Type\StringType(),

			// Enumerated types (assume string fallback if no definition)
			'enum' => $typeDefinition !== null && isset($typeDefinition['enum_labels'])
				? PHPStan\Type\TypeCombinator::union(
					...array_map(
						static fn ($label) => new PHPStan\Type\Constant\ConstantStringType($label),
						$typeDefinition['enum_labels'],
					),
				)
				: new PHPStan\Type\StringType(),

			// ranges
			'int4range',
			'int8range',
			'numrange',
			'tsrange',
			'tstzrange',
			'daterange' => new PHPStan\Type\StringType(),

			// geometry
			'point',
			'line',
			'lseg',
			'box',
			'path',
			'polygon',
			'circle' => new PHPStan\Type\StringType(),

			// ts
			'tsvector',
			'tsquery' => new PHPStan\Type\StringType(),

			default => null,
		};

		if ($type !== null) {
			$result = $type;
		}

		if ($isArray) {
			$result = new PHPStan\Type\ArrayType(new PHPStan\Type\MixedType(), $result);
		}

		if ($typeDefinition !== null) {
			foreach ($typeDefinition['sub_tree'] as $subtree) {
				if ($subtree['expr_type'] === 'check') {
					$checkConstraints = $database->createQueryTypeResolver(null)->processWhereClauses(
						new Query\Context([], [], new Query\TypeProvider(null)),
						$subtree['sub_tree']['sub_tree'],
					);

					if (isset($checkConstraints[$columnName])) {
						$result = PHPStan\Type\TypeCombinator::intersect($result, $checkConstraints[$columnName]);
					}
				}
			}
		}

		return $result;
	}



	/**
	 * @param array<mixed> $typeDefinition
	 */
	private static function resolveNativeSqliteType(
		Database $database,
		string $columnName,
		string $type,
		?array $typeDefinition,
	): PHPStan\Type\Type
	{
		$result = new PHPStan\Type\MixedType();

		$parts = explode(' ', $type);

		foreach ($parts as $part) {
			if (str_ends_with($part, 'int')) {
				$result = new PHPStan\Type\IntegerType();

				break;
			}

			$type = match ($part) {
				'any' => new PHPStan\Type\MixedType(),
				// boolean
				'bool',
				'boolean' => new PHPStan\Type\UnionType([
					new PHPStan\Type\Constant\ConstantIntegerType(0),
					new PHPStan\Type\Constant\ConstantIntegerType(1),
				]),
				// float
				'real',
				'double',
				'float',
				'float4',
				'float8' => new PHPStan\Type\FloatType(),
				// int
				'integer',
				'int2',
				'int4',
				'int8' => new PHPStan\Type\IntegerType(),
				// number
				'numeric',
				'decimal',
				'money',
				'date',
				'datetime',
				'string' => new PHPStan\Type\UnionType([
					new PHPStan\Type\IntegerType(),
					new PHPStan\Type\FloatType(),
				]),
				// string
				'blob',
				'character',
				'clob',
				'longvarchar',
				'nchar',
				'nvarchar',
				'text',
				'varchar' => new PHPStan\Type\StringType(),
				default => null,
			};

			if ($type !== null) {
				$result = $type;

				break;
			}
		}

		if ($typeDefinition !== null) {
			foreach ($typeDefinition['sub_tree'] as $subtree) {
				if ($subtree['expr_type'] === 'check') {
					$checkConstraints = $database->createQueryTypeResolver(null)->processWhereClauses(
						new Query\Context([], [], new Query\TypeProvider(null)),
						$subtree['sub_tree']['sub_tree'],
					);

					if (isset($checkConstraints[$columnName])) {
						$result = PHPStan\Type\TypeCombinator::intersect($result, $checkConstraints[$columnName]);
					}
				}
			}
		}

		return $result;
	}

}
