<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use PHPStan;
use PhpParser;


final class FetchMethods
{

	/**
	 * @param list<string> $rowClasses
	 */
	public static function resolveFetch(
		PHPStan\Type\Type $rowType,
		array $rowClasses,
		bool $normalize,
	): PHPStan\Type\Type
	{
		if ($rowClasses === []) {
			return $rowType;
		}

		$nonNullRowType = self::prepareRowType($rowType, normalize: $normalize);

		$result = PHPStan\Type\TypeCombinator::union(
			...array_map(
				static fn ($rowClass) => new PHPStan\Type\Generic\GenericObjectType(
					$rowClass,
					[$nonNullRowType],
				),
				$rowClasses,
			),
		);

		if ($rowType->isNull()->no() === false) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result;
	}



	/**
	 * @param list<string> $rowClasses
	 */
	public static function resolveFetchAll(
		PHPStan\Type\Type $rowType,
		array $rowClasses,
	): PHPStan\Type\Type
	{
		$result = PHPStan\Type\TypeCombinator::intersect(
			new PHPStan\Type\ArrayType(
				new PHPStan\Type\IntegerType(),
				PHPStan\Type\TypeCombinator::removeNull(self::resolveFetch($rowType, $rowClasses, normalize: true)),
			),
			new PHPStan\Type\Accessory\AccessoryArrayListType(),
		);

		if ($rowType->isNull()->no()) {
			$result = PHPStan\Type\TypeCombinator::intersect(
				new PHPStan\Type\Accessory\NonEmptyArrayType(),
				$result,
			);
		}

		return $result;
	}



	/**
	 * @param list<string> $rowClasses
	 * @param array<PhpParser\Node\Arg> $args
	 */
	public static function resolveFetchAssoc(
		PHPStan\Type\Type $rowType,
		array $rowClasses,
		array $args,
		PHPStan\Analyser\Scope $scope,
	): PHPStan\Type\Type
	{
		if ($args === []) {
			return new PHPStan\Type\ErrorType();
		}

		$assocs = $scope
			->getType($args[0]->value)
			->getConstantStrings();

		$nonNullRowType = self::prepareRowType($rowType, normalize: true);

		$result = [];

		foreach ($assocs as $assoc) {
			$assoc = preg_split(
				'/(\[\]|->|=|\|)/',
				$assoc->getValue(),
				-1,
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
			);

			if ($assoc === false) {
				if (count($assocs) === 1) {
					return new PHPStan\Type\ErrorType();
				} else {
					continue;
				}
			}

			if ($assoc === []) {
				$assoc = ['[]'];
			}

			if ($assoc[count($assoc) - 1] === '->') {
				array_pop($assoc);
			}

			$typeModifications = [];

			for ($i = 0; $i < count($assoc); $i++) {
				$as = $assoc[$i];

				if ($as === '[]') {
					$typeModifications[] = static fn (PHPStan\Type\Type $type) => PHPStan\Type\TypeCombinator::intersect(
						new PHPStan\Type\Accessory\AccessoryArrayListType(),
						new PHPStan\Type\ArrayType(
							new PHPStan\Type\IntegerType(),
							$type,
						),
					);
				} elseif ($as === '->') {
					if (isset($assoc[$i + 1]) === false) {
						continue;
					}

					$typeModifications[] = static fn (PHPStan\Type\Type $type) => self::resolveFetch(
						$nonNullRowType->setOffsetValueType(
							new PHPStan\Type\Constant\ConstantStringType($assoc[$i + 1]),
							new PHPStan\Type\ArrayType(
								$nonNullRowType->getOffsetValueType(new PHPStan\Type\Constant\ConstantStringType($assoc[$i + 1])),
								$type,
							),
						),
						$rowClasses,
						normalize: false,
					);

					$i++;
				} elseif ($as === '=') {
					if (isset($assoc[$i + 1]) === false) {
						continue;
					}

					$typeModifications[] = static fn (PHPStan\Type\Type $type) => $nonNullRowType->getOffsetValueType(
						new PHPStan\Type\Constant\ConstantStringType($assoc[$i + 1]),
					);

					$i++;
				} elseif ($as !== '|') {
					$typeModifications[$as] = static function (PHPStan\Type\Type $type) use ($as, $nonNullRowType): PHPStan\Type\Type {
						if ($type->isList()->yes()) {
							$type = PHPStan\Type\TypeCombinator::intersect(
								new PHPStan\Type\Accessory\NonEmptyArrayType(),
								$type,
							);
						}

						return new PHPStan\Type\ArrayType(
							$nonNullRowType->getOffsetValueType(
								new PHPStan\Type\Constant\ConstantStringType($as),
							),
							$type,
						);
					};
				}
			}

			$resultType = PHPStan\Type\TypeCombinator::removeNull(self::resolveFetch($rowType, $rowClasses, normalize: true));

			foreach (array_reverse($typeModifications) as $typeModification) {
				$resultType = $typeModification($resultType);
			}

			$result[] = $resultType;
		}

		if ($result === []) {
			return new PHPStan\Type\MixedType();
		}

		return PHPStan\Type\TypeCombinator::union(...$result);
	}



	/**
	 * @param array<PhpParser\Node\Arg> $args
	 */
	public static function resolveFetchPairs(
		PHPStan\Type\Type $rowType,
		array $args,
		PHPStan\Analyser\Scope $scope,
	): PHPStan\Type\Type
	{
		$rowType = self::prepareRowType($rowType, normalize: true);

		if ($args === []) {
			return PHPStan\Type\TypeCombinator::union(
				...array_map(
					static function (PHPStan\Type\Constant\ConstantArrayType $constantArrayType): PHPStan\Type\Type {
						$valueTypes = $constantArrayType->getValueTypes();

						if (count($valueTypes) === 1) {
							return PHPStan\Type\TypeCombinator::intersect(
								new PHPStan\Type\ArrayType(
									new PHPStan\Type\IntegerType(),
									$valueTypes[0],
								),
								new PHPStan\Type\Accessory\AccessoryArrayListType(),
							);
						}

						return new PHPStan\Type\ArrayType(
							$valueTypes[0],
							$valueTypes[1],
						);
					},
					$rowType->getConstantArrays(),
				),
			);
		}

		if (count($args) === 2) {
			return new PHPStan\Type\ArrayType(
				$rowType->getOffsetValueType(
					$scope->getType($args[0]->value),
				),
				$rowType->getOffsetValueType(
					$scope->getType($args[1]->value),
				),
			);
		}

		return new PHPStan\Type\ErrorType();
	}



	public static function resolveFetchSingle(
		PHPStan\Type\Type $rowType,
	): PHPStan\Type\Type
	{
		$result = self::prepareRowType($rowType, normalize: true)->getFirstIterableValueType();

		if ($rowType->isNull()->no() === false) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result;
	}



	private static function prepareRowType(
		PHPStan\Type\Type $rowType,
		bool $normalize,
	): PHPStan\Type\Type
	{
		$rowType = PHPStan\Type\TypeCombinator::removeNull($rowType);

		if ($normalize) {
			$rowType = $rowType->traverse(
				static fn ($type) => $type->isArray()->yes()
					? new PHPStan\Type\StringType()
					: $type,
			);
		}

		return $rowType;
	}

}
