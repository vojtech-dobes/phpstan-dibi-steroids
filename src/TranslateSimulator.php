<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use Dibi;
use PHPStan;
use stdClass;


final class TranslateSimulator
{

	public const /* php83 float */ Float = 6.7274;
	public const /* php83 int */ Integer = 67274;
	public const /* php83 string */ String = 'phpstan_generated_string';

	public static Dibi\Connection $dibi;

	private static PHPStan\Type\ObjectType $expressionType;
	private static PHPStan\Type\ObjectType $literalType;
	private static PHPStan\Type\ObjectType $stdClassType;



	public function __construct(
		private readonly Dibi\Connection $connection,
	)
	{
		self::$expressionType ??= new PHPStan\Type\ObjectType(Dibi\Expression::class);
		self::$literalType ??= new PHPStan\Type\ObjectType(Dibi\Literal::class);
		self::$stdClassType ??= new PHPStan\Type\ObjectType(stdClass::class);
	}



	/**
	 * @param array<PHPStan\Type\Type> $argumentTypes
	 * @param array<bool>|null $argumentUnpacking
	 * @return array{list<string>, list<Dibi\Exception>}
	 */
	public function buildSqlStrings(
		array $argumentTypes,
		?array $argumentUnpacking = [],
	): array
	{
		$argumentLists = [
			[],
		];

		foreach ($argumentTypes as $key => $argumentType) {
			$isVariadic = $argumentUnpacking[$key] ?? false;

			if ($isVariadic) {
				if ($argumentType->getConstantArrays() === []) {
					continue;
				}
			}

			$argumentValues = $this->getValuesRepresentingType($argumentType);

			if ($argumentValues === []) {
				continue;
			}

			$argumentLists = array_merge(
				...array_map(
					static fn ($argumentValue) => array_map(
						static fn ($argumentList) => [
							...$argumentList,
							...($isVariadic ? $argumentValue : [$argumentValue]),
						],
						$argumentLists,
					),
					$argumentValues,
				),
			);
		}

		$validSqls = [];
		$invalidSqls = [];

		foreach ($argumentLists as $argumentList) {
			try {
				$validSqls[] = $this->connection->translate(...$argumentList);
			} catch (Dibi\Exception $e) {
				$invalidSqls[] = $e;
			}
		}

		return [$validSqls, $invalidSqls];
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesRepresentingType(
		PHPStan\Type\Type $type,
	): array
	{
		return match (true) {
			$type instanceof PHPStan\Type\UnionType => $this->getValuesFromUnionType($type),
			$type instanceof PHPStan\Type\Constant\ConstantArrayType => $this->getValuesFromConstantArrayType($type),
			($scalarValues = $type->getConstantScalarValues()) !== [] => $scalarValues,
			$type->isBoolean()->yes() => [true, false],
			$type->isFloat()->yes() => [self::Float],
			$type->isInteger()->yes() => [self::Integer],
			$type->isString()->yes() => [self::String],
			$type->isArray()->yes() => $this->getValuesFromArrayType($type),
			$type->isEnum()->yes() => $this->getValuesFromEnumType($type),
			self::$expressionType->isSuperTypeOf($type)->yes() => $this->getValuesFromDibiExpression($type),
			self::$literalType->isSuperTypeOf($type)->yes() => $this->getValuesFromDibiLiteral($type),
			self::$stdClassType->isSuperTypeOf($type)->yes() => [(object) []],
			$type->isObject()->yes() => $this->getValuesFromObjectType($type),
			default => [],
		};
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromArrayType(
		PHPStan\Type\Type $type,
	): array
	{
		if ($type->isIterableAtLeastOnce()->no()) {
			return [[]];
		}

		$constantArrayTypes = $type->getConstantArrays();

		if ($constantArrayTypes !== []) {
			return array_merge(
				...array_map(
					fn ($constantArrayType) => $this->getValuesRepresentingType($constantArrayType),
					$constantArrayTypes,
				),
			);
		}

		$values = $this->getValuesRepresentingType(
			$type->getIterableValueType(),
		);

		if ($values === []) {
			return [
				new Dibi\Literal('/* generic array */'),
			];
		}

		$keyType = $type->getIterableKeyType();

		if ($keyType->isInteger()->no() === false) {
			$keys = [0, 1, 2];
		} elseif ($keyType->isString()->no() === false) {
			$constantKeys = $keyType->getConstantStrings();

			if ($constantKeys !== []) {
				$keys = array_map(
					static fn ($constantKey) => $constantKey->getValue(),
					$constantKeys,
				);
			} else {
				$keys = ['phpstan_key_a', 'phpstan_key_b', 'phpstan_key_c'];
			}
		} else {
			return [
				new Dibi\Literal('/* generic array */'),
			];
		}

		return array_map(
			static fn ($value) => array_combine(
				$keys,
				array_fill(0, count($keys), $value),
			),
			$values,
		);
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromConstantArrayType(
		PHPStan\Type\Constant\ConstantArrayType $type,
	): array
	{
		$resultArrays = [[]];

		foreach ($type->getKeyTypes() as $keyType) {
			$valueType = $type->getOffsetValueType($keyType);

			$newResultArrays = [];

			foreach ($this->getValuesRepresentingType($valueType) as $keyValue) {
				foreach ($resultArrays as $resultArray) {
					$newResultArrays[] = array_merge(
						$resultArray,
						[
							$keyType->getValue() => $keyValue,
						],
					);

					if (count($newResultArrays) > 100) {
						break;
					}
				}
			}

			$resultArrays = $newResultArrays;
		}

		return $resultArrays;
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromDibiExpression(
		PHPStan\Type\Type $type,
	): array
	{
		$constantArrayTypes = $type
			->getTemplateType(Dibi\Expression::class, 'TValues')
			->getConstantArrays();

		if ($constantArrayTypes === []) {
			return [
				new Dibi\Literal('/* generic Dibi\\Expression */'),
			];
		}

		return array_map(
			static fn ($sqlString) => new Dibi\Literal($sqlString),
			array_merge(
				...array_map(
					fn ($constantArrayType) => $this->buildSqlStrings($constantArrayType->getValueTypes())[0],
					$constantArrayTypes,
				),
			),
		);
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromDibiLiteral(
		PHPStan\Type\Type $type,
	): array
	{
		$constantScalarTypes = $type
			->getTemplateType(Dibi\Literal::class, 'TValue')
			->getConstantScalarTypes();

		if ($constantScalarTypes === []) {
			return [
				new Dibi\Literal('/* generic Dibi\\Literal */'),
			];
		}

		return array_merge(
			...array_map(
				static fn ($scalarType) => array_map(
					static fn ($stringType) => new Dibi\Literal($stringType->getValue()),
					$scalarType->toString()->getConstantStrings(),
				),
				$constantScalarTypes,
			),
		);
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromEnumType(
		PHPStan\Type\Type $type,
	): array
	{
		return array_merge(
			...array_map(
				static fn ($enumCase) => $enumCase->getBackingValueType() !== null
					? $enumCase->getBackingValueType()->getConstantScalarValues()
					: [],
				$type->getEnumCases(),
			),
		);
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromObjectType(
		PHPStan\Type\Type $type,
	): array
	{
		return $type->toString()->getConstantScalarValues();
	}



	/**
	 * @return list<mixed>
	 */
	private function getValuesFromUnionType(
		PHPStan\Type\UnionType $type,
	): array
	{
		return array_merge(
			...array_map(
				fn ($unionMemberType) => $this->getValuesRepresentingType($unionMemberType),
				$type->getTypes(),
			),
		);
	}

}
