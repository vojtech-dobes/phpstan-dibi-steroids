<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Typeof implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType === DatabaseType::Sqlite) {
			return PHPStan\Type\TypeCombinator::union(
				new PHPStan\Type\Constant\ConstantStringType('blob'),
				new PHPStan\Type\Constant\ConstantStringType('integer'),
				new PHPStan\Type\Constant\ConstantStringType('null'),
				new PHPStan\Type\Constant\ConstantStringType('real'),
				new PHPStan\Type\Constant\ConstantStringType('text'),
			);
		}

		return null;
	}

}
