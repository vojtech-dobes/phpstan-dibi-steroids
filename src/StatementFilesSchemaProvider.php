<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use PHPStan;


final class StatementFilesSchemaProvider implements SchemaProvider
{

	/**
	 * @param array<string, list<string>|string> $statementFiles
	 */
	public function __construct(
		private readonly array $statementFiles,
	) {}



	/**
	 * @throws PHPStan\ShouldNotHappenException
	 */
	public function getDatabaseSchema(Database $database): Schema\Database
	{
		return $this->createStatementsSchemaProvider($database->name)->getDatabaseSchema($database);
	}



	/**
	 * @throws PHPStan\ShouldNotHappenException
	 */
	private function createStatementsSchemaProvider(string $databaseName): StatementsSchemaProvider
	{
		$statements = [];

		$statementFiles = is_array($this->statementFiles[$databaseName])
			? $this->statementFiles[$databaseName]
			: [$this->statementFiles[$databaseName]];

		foreach ($statementFiles as $statementFile) {
			$statementFileContents = file_get_contents($statementFile);

			if ($statementFileContents === false) {
				throw new PHPStan\ShouldNotHappenException(
					"File {$statementFile} can't be read",
				);
			}

			$statements = [
				...$statements,
				...explode(";\n", $statementFileContents),
			];
		}

		return new StatementsSchemaProvider([
			$databaseName => $statements,
		]);
	}

}
