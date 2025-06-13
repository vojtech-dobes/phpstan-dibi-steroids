<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Schema;

use PHPStan;


final class Column
{

	public function __construct(
		public readonly string $name,
		public readonly PHPStan\Type\Type $readType,
		public readonly PHPStan\Type\Type $writeType,
		public readonly bool $hasDefaultValue,
		public readonly bool $isAutoIncrement,
		public readonly bool $isNullable,
	) {}



	public function getReadPhpType(): string
	{
		$result = $this->readType;

		if ($this->isNullable) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result->describe(PHPStan\Type\VerbosityLevel::precise());
	}



	public function getWritePhpType(): string
	{
		$result = $this->writeType;

		if ($this->isNullable) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result->describe(PHPStan\Type\VerbosityLevel::precise());
	}

}
