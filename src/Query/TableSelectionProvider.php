<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;


final class TableSelectionProvider implements SelectionProvider
{

	public function __construct(
		private readonly TypeProvider $typeProvider,
		public readonly string $tableName,
	) {}



	public function getColumnType(string $columnName): PHPStan\Type\Type
	{
		return $this->typeProvider->getColumnType($this->tableName, $columnName);
	}



	/**
	 * @return list<string>
	 */
	public function listColumns(): array
	{
		return $this->typeProvider->listTableColumns($this->tableName);
	}

}
