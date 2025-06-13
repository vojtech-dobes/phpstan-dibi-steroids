<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use Vojtechdobes\PHPStan\Dibi\DatabaseType;


interface FunctionInterfaceName
{

	/**
	 * @param array<mixed> $subtree
	 */
	function getName(
		DatabaseType $databaseType,
		array $subtree,
	): ?string;

}
