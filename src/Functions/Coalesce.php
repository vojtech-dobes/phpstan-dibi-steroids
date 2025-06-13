<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Coalesce implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		$arguments = array_map(
			static fn ($argument) => PHPStan\Type\TypeCombinator::removeNull(
				$subtreeResolver->getSubtreeType($argument),
			),
			array_slice($subtree['sub_tree'], 0, -1),
		);

		return PHPStan\Type\TypeCombinator::union(
			$subtreeResolver->getSubtreeType($subtree['sub_tree'][count($subtree['sub_tree']) - 1]),
			...$arguments,
		);
	}

}
