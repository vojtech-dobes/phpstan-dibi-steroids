<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Extensions;

use PHPStan;


class DibiRowPropertyReflection implements PHPStan\Reflection\PropertyReflection
{

	public function __construct(
		private readonly PHPStan\Reflection\ClassReflection $classReflection,
		private readonly PHPStan\Type\Type $type,
	) {}



	public function getDeclaringClass(): PHPStan\Reflection\ClassReflection
	{
		return $this->classReflection;
	}



	public function isStatic(): bool
	{
		return false;
	}



	public function isPrivate(): bool
	{
		return false;
	}



	public function isPublic(): bool
	{
		return true;
	}



	public function getDocComment(): ?string
	{
		return null;
	}



	public function getReadableType(): PHPStan\Type\Type
	{
		return $this->type;
	}



	public function getWritableType(): PHPStan\Type\Type
	{
		return $this->type;
	}



	public function canChangeTypeAfterAssignment(): bool
	{
		return false;
	}



	public function isReadable(): bool
	{
		return true;
	}



	public function isWritable(): bool
	{
		return false;
	}



	public function isDeprecated(): PHPStan\TrinaryLogic
	{
		return PHPStan\TrinaryLogic::createNo();
	}



	public function getDeprecatedDescription(): ?string
	{
		return null;
	}



	public function isInternal(): PHPStan\TrinaryLogic
	{
		return PHPStan\TrinaryLogic::createNo();
	}

}
