<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Ifnull implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		return PHPStan\Type\TypeCombinator::union(
			$subtreeResolver->getSubtreeType($subtree['sub_tree'][0]),
			$subtreeResolver->getSubtreeType($subtree['sub_tree'][1]),
			new PHPStan\Type\NullType(),
		);
	}

}
