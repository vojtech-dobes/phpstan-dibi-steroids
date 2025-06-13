<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\Helpers;
use Vojtechdobes\PHPStan\Dibi\SchemaClassOraculum;
use Vojtechdobes\PHPStan\Dibi\TranslateSimulator;


final class TypeProvider
{

	/**
	 * @param array<string, PHPStan\Type\Type> $virtualTables
	 */
	public function __construct(
		private readonly ?SchemaClassOraculum $schemaClassOraculum,
		private readonly array $virtualTables = [],
	) {}



	public function addVirtualTable(string $name, PHPStan\Type\Type $rowType): self
	{
		if (
			array_key_exists($name, $this->virtualTables)
			|| ($this->schemaClassOraculum?->hasTable($name) ?? false)
		) {
			return $this;
		}

		return new self(
			$this->schemaClassOraculum,
			$this->virtualTables + [
				$name => $rowType,
			],
		);
	}



	public function getColumnType(
		string $tableName,
		string $columnName,
	): PHPStan\Type\Type
	{
		$tableName = Helpers::normalizeName($tableName);
		$columnName = Helpers::normalizeName($columnName);

		if ($tableName === TranslateSimulator::String || $columnName === TranslateSimulator::String) {
			return new PHPStan\Type\MixedType();
		}

		if (isset($this->virtualTables[$tableName])) {
			return $this->virtualTables[$tableName]->getOffsetValueType(
				new PHPStan\Type\Constant\ConstantStringType($columnName),
			);
		}

		if ($this->schemaClassOraculum !== null) {
			return $this->schemaClassOraculum->getColumnType($tableName, $columnName);
		}

		return new PHPStan\Type\ErrorType();
	}



	/**
	 * @return list<string>
	 */
	public function listTableColumns(
		string $tableName,
	): array
	{
		$tableName = Helpers::normalizeName($tableName);

		if ($tableName === TranslateSimulator::String) {
			return [];
		}

		if (isset($this->virtualTables[$tableName])) {
			return array_map(
				static fn ($constantStringType) => $constantStringType->getValue(),
				$this->virtualTables[$tableName]->getIterableKeyType()->getConstantStrings(),
			);
		}

		if ($this->schemaClassOraculum !== null) {
			return $this->schemaClassOraculum->listTableColumns($tableName);
		}

		return [];
	}



	public function hasView(string $viewName): bool
	{
		if ($this->schemaClassOraculum === null) {
			return false;
		}

		return $this->schemaClassOraculum->hasView($viewName);
	}



	public function getViewDefinition(string $viewName): string
	{
		if ($this->schemaClassOraculum === null) {
			throw new PHPStan\ShouldNotHappenException();
		}

		return $this->schemaClassOraculum->getViewDefinition($viewName);
	}

}
