<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;


final class Helpers
{

	public static function createSchemaClassName(string $databaseName): string
	{
		return sprintf(
			'DibiSchemaClass_%s',
			md5($databaseName),
		);
	}



	public static function normalizeName(string $name): string
	{
		return trim($name, '\'"`[]');
	}

}
