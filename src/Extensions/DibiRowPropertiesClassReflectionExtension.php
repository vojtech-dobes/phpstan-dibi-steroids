<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Extensions;

use Dibi;
use PHPStan;


final class DibiRowPropertiesClassReflectionExtension implements PHPStan\Reflection\PropertiesClassReflectionExtension
{

	private PHPStan\Type\ObjectType $dibiRowObjectType;



	public function __construct()
	{
		$this->dibiRowObjectType = new PHPStan\Type\ObjectType(Dibi\Row::class);
	}



	public function hasProperty(
		PHPStan\Reflection\ClassReflection $classReflection,
		string $propertyName,
	): bool
	{
		if ($this->dibiRowObjectType->isSuperTypeOf(new PHPStan\Type\ObjectType($classReflection->getName()))->yes() === false) {
			return false;
		}

		while ($classReflection->getName() !== Dibi\Row::class) {
			$classReflection = $classReflection->getParentClass();

			if ($classReflection === null) {
				return false;
			}
		}

		$rowType = $classReflection
			->getActiveTemplateTypeMap()
			->getType('TRow');

		if ($rowType === null) {
			return false;
		}

		return $rowType
			->hasOffsetValueType(new PHPStan\Type\Constant\ConstantStringType($propertyName))
			->yes();
	}



	/**
	 * @throws PHPStan\ShouldNotHappenException
	 */
	public function getProperty(
		PHPStan\Reflection\ClassReflection $classReflection,
		string $propertyName,
	): PHPStan\Reflection\PropertyReflection
	{
		while ($classReflection->getName() !== Dibi\Row::class) {
			$classReflection = $classReflection->getParentClass();

			if ($classReflection === null) {
				throw new PHPStan\ShouldNotHappenException();
			}
		}

		$rowType = $classReflection
			->getActiveTemplateTypeMap()
			->getType('TRow');

		if ($rowType === null) {
			throw new PHPStan\ShouldNotHappenException();
		}

		return new DibiRowPropertyReflection(
			$classReflection,
			$rowType->getOffsetValueType(new PHPStan\Type\Constant\ConstantStringType($propertyName)),
		);
	}

}
