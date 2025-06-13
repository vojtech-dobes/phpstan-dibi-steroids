<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Random implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		return match ($databaseType) {
			DatabaseType::Postgres => new PHPStan\Type\FloatType(),
			DatabaseType::Sqlite => new PHPStan\Type\IntegerType(),
			default => null,
		};
	}

}
