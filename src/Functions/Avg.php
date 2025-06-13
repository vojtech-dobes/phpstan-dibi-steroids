<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Avg implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		$result = new PHPStan\Type\FloatType();

		if ($subtreeResolver->getSubtreeType($subtree['sub_tree'][0])->getConstantScalarValues() === []) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result;
	}

}
