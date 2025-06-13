<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Schema;

use LogicException;


final class Database
{

	/**
	 * @param list<Table> $tables
	 * @param list<View> $views
	 */
	public function __construct(
		public readonly array $tables,
		public readonly array $views,
	) {}



	public function getTableColumn(string $tableName, string $columnName): Column
	{
		foreach ($this->tables as $table) {
			if ($table->name === $tableName) {
				foreach ($table->columns as $column) {
					if ($column->name === $columnName) {
						return $column;
					}
				}
			}
		}

		throw new LogicException("Column '{$tableName}.{$columnName}' doesn't exist");
	}

}
