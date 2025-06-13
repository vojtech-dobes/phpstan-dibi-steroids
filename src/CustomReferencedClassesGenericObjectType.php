<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use PHPStan;


class CustomReferencedClassesGenericObjectType extends PHPStan\Type\Generic\GenericObjectType
{

	/**
	 * @param list<class-string> $customReferencedClasses
	 * @param array<int, PHPStan\Type\Type> $types
	 */
	public function __construct(
		private readonly array $customReferencedClasses,
		string $mainType,
		array $types,
		?PHPStan\Type\Type $subtractedType = null,
	)
	{
		parent::__construct(
			$mainType,
			$types,
			$subtractedType,
		);
	}



	public function getReferencedClasses(): array
	{
		return array_values(
			array_unique([
				...parent::getReferencedClasses(),
				...$this->customReferencedClasses,
			]),
		);
	}

}
