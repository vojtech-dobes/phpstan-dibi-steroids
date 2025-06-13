<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Max implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		if (count($subtree['sub_tree']) === 1) {
			$givenType = $subtreeResolver->getSubtreeType($subtree['sub_tree'][0]);
			$givenScalarValues = $givenType->getConstantScalarValues();

			return $givenScalarValues !== []
				? $givenType
				: PHPStan\Type\TypeCombinator::addNull($givenType);
		}

		return PHPStan\Type\TypeCombinator::union(
			...array_map(
				static fn ($subtree) => $subtreeResolver->getSubtreeType($subtree),
				$subtree['sub_tree'],
			),
		);
	}

}
