<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\Helpers;


final class Context
{

	/**
	 * @param array<string, PHPStan\Type\Type> $columnConstraints
	 * @param array<DataSource> $dataSources,
	 */
	public function __construct(
		private readonly array $columnConstraints,
		private readonly array $dataSources,
		public readonly TypeProvider $typeProvider,
	) {}



	/**
	 * @return iterable<string, Selection>
	 */
	public function getWildcardSelections(?string $tableName): iterable
	{
		if ($tableName === null) {
			$dataSources = $this->dataSources;
		} else {
			if (array_key_exists($tableName, $this->dataSources) === false) {
				return;
			}

			$dataSources = [$this->dataSources[$tableName]];
		}

		foreach ($dataSources as $dataSource) {
			foreach ($dataSource->listColumns() as $columnName) {
				yield $columnName => new Selection(
					isAggregation: false,
					type: $this->getColumnType($dataSource->alias, $columnName),
				);
			}
		}
	}



	public function getColumnType(?string $tableName, string $columnName): PHPStan\Type\Type
	{
		$columnName = Helpers::normalizeName($columnName);

		if ($tableName !== null) {
			$dataSource = $this->dataSources[$tableName] ?? null;
		} else {
			$dataSource = null;

			foreach ($this->dataSources as $potentialDataSource) {
				if (!$potentialDataSource->getColumnType($columnName) instanceof PHPStan\Type\ErrorType) {
					$dataSource = $potentialDataSource;

					break;
				}
			}
		}

		if ($dataSource === null) {
			return new PHPStan\Type\ErrorType();
		}

		$result = $dataSource->getColumnType($columnName);

		if (isset($this->columnConstraints[$columnName])) {
			$result = PHPStan\Type\TypeCombinator::intersect($result, $this->columnConstraints[$columnName]);
		}

		if ($dataSource->joinType === JoinType::Left) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result;
	}

}
