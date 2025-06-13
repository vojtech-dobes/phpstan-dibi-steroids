<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use PHPStan;


final class SchemaClassOraculum
{

	public function __construct(
		private readonly PHPStan\Reflection\ClassReflection $schemaReflection,
	) {}



	public function hasTable(
		string $tableName,
	): bool
	{
		return $this->schemaReflection->hasProperty('table__' . $tableName . '__columns');
	}



	public function hasView(
		string $viewName,
	): bool
	{
		return $this->schemaReflection->hasProperty('view__' . $viewName . '__definition');
	}



	public function getColumnType(
		string $tableName,
		string $columnName,
	): PHPStan\Type\Type
	{
		$propertyName = 'table__' . $tableName . '__column__' . $columnName . '__read';

		if ($this->schemaReflection->hasProperty($propertyName) === false) {
			return new PHPStan\Type\ErrorType();
		}

		return $this->schemaReflection
			->getNativeProperty($propertyName)
			->getPhpDocType();
	}



	/**
	 * @return list<string>
	 */
	public function listTableColumns(
		string $tableName,
	): array
	{
		$propertyName = 'table__' . $tableName . '__columns';

		if ($this->schemaReflection->hasProperty($propertyName) === false) {
			return [];
		}

		return $this->schemaReflection
			->getNativeProperty($propertyName)
			->getNativeReflection()
			->getDefaultValue();
	}



	public function getViewDefinition(
		string $viewName,
	): string
	{
		return $this->schemaReflection
			->getNativeProperty('view__' . $viewName . '__definition')
			->getNativeReflection()
			->getDefaultValue();
	}

}
