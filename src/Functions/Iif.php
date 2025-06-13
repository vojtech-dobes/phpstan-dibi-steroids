<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Iif implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType !== DatabaseType::Sqlite) {
			return null;
		}

		if (count($subtree['sub_tree']) % 2 === 0) {
			$result = PHPStan\Type\TypeCombinator::union(
				...array_map(
					static fn ($itemSubtree) => $subtreeResolver->getSubtreeType($itemSubtree),
					array_filter(
						$subtree['sub_tree'],
						static fn ($i) => ($i + 1) % 2 === 0,
						ARRAY_FILTER_USE_KEY,
					),
				),
			);
		} else {
			$result = $subtreeResolver->getSubtreeType($subtree['sub_tree'][count($subtree['sub_tree']) - 1]);
		}

		return PHPStan\Type\TypeCombinator::addNull($result);
	}

}
