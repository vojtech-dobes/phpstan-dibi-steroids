<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;


interface SelectionProvider
{

	function getColumnType(string $columnName): PHPStan\Type\Type;



	/**
	 * @return list<string>
	 */
	function listColumns(): array;

}
