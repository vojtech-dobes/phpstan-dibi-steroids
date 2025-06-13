<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;


final class DataSource
{

	public function __construct(
		public readonly string $alias,
		public readonly JoinType $joinType,
		public readonly string $name,
		private readonly SelectionProvider $selectionProvider,
	) {}



	public function getColumnType(string $columnName): PHPStan\Type\Type
	{
		return $this->selectionProvider->getColumnType($columnName);
	}



	/**
	 * @return list<string>
	 */
	public function listColumns(): array
	{
		return $this->selectionProvider->listColumns();
	}

}
