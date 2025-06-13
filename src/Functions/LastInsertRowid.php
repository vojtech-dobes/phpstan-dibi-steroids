<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class LastInsertRowid implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType === DatabaseType::Sqlite) {
			return PHPStan\Type\TypeCombinator::addNull(
				PHPStan\Type\IntegerRangeType::fromInterval(0, null),
			);
		}

		return null;
	}

}
