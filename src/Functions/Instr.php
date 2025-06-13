<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Instr implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType === DatabaseType::Postgres) {
			return null;
		}

		return Common::resolveGivenTypeOrNullWithNullableInput(
			Common::resolveGivenTypeOrNullWithNullableInput(
				PHPStan\Type\IntegerRangeType::fromInterval(0, null),
				$subtreeResolver,
				$subtree,
			),
			$subtreeResolver,
			$subtree,
			1,
		);
	}

}
