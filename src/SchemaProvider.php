<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;


interface SchemaProvider
{

	function getDatabaseSchema(Database $database): Schema\Database;

}
