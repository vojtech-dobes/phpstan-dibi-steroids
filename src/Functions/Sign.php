<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Sign implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		return Common::resolveGivenTypeOrNullWithNullableInput(
			PHPStan\Type\TypeCombinator::union(
				new PHPStan\Type\Constant\ConstantIntegerType(-1),
				new PHPStan\Type\Constant\ConstantIntegerType(0),
				new PHPStan\Type\Constant\ConstantIntegerType(1),
			),
			$subtreeResolver,
			$subtree,
		);
	}

}
