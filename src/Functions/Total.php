<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Total implements FunctionInterface, FunctionInterfaceName
{

	/**
	 * @param array<mixed> $subtree
	 */
	public function getName(
		DatabaseType $databaseType,
		array $subtree,
	): ?string
	{
		// if ($databaseType === DatabaseType::Sqlite) {
		// 	return $subtree['base_expr'];
		// }

		return null;
	}



	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		if ($databaseType !== DatabaseType::Sqlite) {
			return null;
		}

		return new PHPStan\Type\FloatType();
	}

}
