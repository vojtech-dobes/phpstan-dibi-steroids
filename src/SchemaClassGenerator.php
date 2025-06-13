<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use Nette;


class SchemaClassGenerator
{

	public function __construct(
		private readonly string $generatedDir,
	) {}



	public function generateSchemaClass(
		Database $database,
		string $className,
		SchemaProvider $schemaProvider,
	): void
	{
		$databaseSchema = $schemaProvider->getDatabaseSchema($database);

		$file = new Nette\PhpGenerator\PhpFile();
		$file->setStrictTypes();

		$class = $file->addClass($className);

		foreach ($databaseSchema->tables as $table) {
			$this->processTable(
				$class,
				$table,
			);
		}

		foreach ($databaseSchema->views as $view) {
			$this->processView(
				$class,
				$view,
			);
		}

		Nette\Utils\FileSystem::write(
			"{$this->generatedDir}/{$className}.php",
			(string) $file,
		);
	}



	private function processTable(
		Nette\PhpGenerator\ClassType $class,
		Schema\Table $table,
	): void
	{
		foreach ($table->columns as $column) {
			$class->addProperty('table__' . $table->name . '__column__' . $column->name . '__read')
				->setPublic()
				->setComment('@var ' . $column->getReadPhpType());

			$class->addProperty('table__' . $table->name . '__column__' . $column->name . '__update')
				->setPublic()
				->setComment('@var ' . $column->getWritePhpType());
		}

		$class->addProperty('table__' . $table->name . '__columns')
			->setPublic()
			->setValue(
				array_column($table->columns, 'name'),
			);

		$class->addProperty('table__' . $table->name . '__insert')
			->setPublic()
			->addComment(
				sprintf(
					'@var array{%s}',
					implode(
						', ',
						array_map(
							static fn ($column) => sprintf(
								"'%s'%s: %s%s",
								$column->name,
								($column->isNullable || $column->hasDefaultValue || $column->isAutoIncrement) ? '?' : '',
								$column->getWritePhpType(),
								$column->isNullable ? '|null' : '',
							),
							$table->columns,
						),
					),
				),
			);
	}



	private function processView(
		Nette\PhpGenerator\ClassType $class,
		Schema\View $view,
	): void
	{
		$class->addProperty('view__' . $view->name . '__definition')
			->setPublic()
			->setValue($view->definition);
	}

}
