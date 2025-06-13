<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class IfFunction implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType === DatabaseType::Sqlite) {
			return (new Iif())->getReturnType($databaseType, $subtreeResolver, $subtree);
		}

		return PHPStan\Type\TypeCombinator::union(
			$subtreeResolver->getSubtreeType($subtree['sub_tree'][1]),
			$subtreeResolver->getSubtreeType($subtree['sub_tree'][2]),
		);
	}

}
