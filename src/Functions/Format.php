<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Format implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType === DatabaseType::Mariadb || $databaseType === DatabaseType::Mysql) {
			return Common::resolveGivenTypeOrNullWithNullableInput(
				Common::resolveStringOrNullWithNullableInput(
					$subtreeResolver,
					$subtree,
				),
				$subtreeResolver,
				$subtree,
				1,
			);
		}

		if ($databaseType === DatabaseType::Sqlite) {
			return Common::resolveStringOrNullWithNullableInput(
				$subtreeResolver,
				$subtree,
			);
		}

		return null;
	}

}
