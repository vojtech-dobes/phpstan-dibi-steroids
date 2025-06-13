<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Length implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		return Common::resolveGivenTypeOrNullWithNullableInput(
			PHPStan\Type\IntegerRangeType::fromInterval(0, null),
			$subtreeResolver,
			$subtree,
		);
	}

}
