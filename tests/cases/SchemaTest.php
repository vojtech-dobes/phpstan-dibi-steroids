<?php declare(strict_types=1);

namespace Vojtechdobes\Tests;

use Dibi;
use Nette;
use PHPUnit;
use Vojtechdobes;


final class SchemaTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @return iterable<string, array<mixed>>
	 */
	public static function dataCases(): iterable
	{
		foreach (glob(__DIR__ . '/../data/SchemaTest/*.php') ?: [] as $file) {
			yield from array_map(
				static fn ($case) => [
					Vojtechdobes\PHPStan\Dibi\DatabaseType::from(explode('.', basename($file))[0]),
					...$case,
				],
				require_once($file),
			);
		}
	}



	/**
	 * @param array<string, string>|string $expectedType
	 */
	#[PHPUnit\Framework\Attributes\DataProvider('dataCases')]
	public function testSchemas(
		Vojtechdobes\PHPStan\Dibi\DatabaseType $databaseType,
		string $columnDefinition,
		array|string $expectedType,
	): void
	{
		$connection = self::getDatabase($databaseType);

		$tableName = 'foo';
		$columnName = 'foo';

		if (str_contains($columnDefinition, ';')) {
			$createTableStatement = $columnDefinition;

			foreach (explode(';', $columnDefinition) as $statement) {
				$statement = trim($statement);

				if ($statement === '') {
					continue;
				}

				$connection->nativeQuery($statement);
			}
		} else {
			$createTableStatement = match ($databaseType) {
				$databaseType::Mariadb => sprintf(
					'CREATE TABLE %%n (%%n %s)',
					$columnDefinition,
				),
				$databaseType::Mysql => sprintf(
					'CREATE TABLE %%n (%%n %s)',
					$columnDefinition,
				),
				$databaseType::Postgres => sprintf(
					'CREATE TABLE %%n (%%n %s)',
					$columnDefinition,
				),
				$databaseType::Sqlite => sprintf(
					'CREATE TABLE %%n (%%n %s)',
					$columnDefinition,
				),
			};

			$createTableStatement = $connection->translate($createTableStatement, $tableName, $columnName);

			$connection->query('DROP TABLE IF EXISTS %n', $tableName);
			$connection->nativeQuery($createTableStatement);
		}

		$config = new Vojtechdobes\PHPStan\Dibi\Config(
			databases: null,
			database: $connection,
			generatedDir: '',
		);

		$database = $config->getDatabase('main');

		$reflectorSchemaProvider = new Vojtechdobes\PHPStan\Dibi\ReflectorSchemaProvider();

		$statementsSchemaProvider = new Vojtechdobes\PHPStan\Dibi\StatementsSchemaProvider([
			'main' => $createTableStatement,
		]);

		if (is_string($expectedType)) {
			$expectedType = [$columnName => $expectedType];
		}

		foreach ($expectedType as $expectedColumnName => $columnExpectedType) {
			self::assertSame(
				$columnExpectedType,
				$statementsSchemaProvider
					->getDatabaseSchema($database)
					->getTableColumn($tableName, $expectedColumnName)
					->getReadPhpType(),
			);

			self::assertSame(
				$columnExpectedType,
				$reflectorSchemaProvider
					->getDatabaseSchema($database)
					->getTableColumn($tableName, $expectedColumnName)
					->getReadPhpType(),
			);
		}
	}



	public static function getDatabase(
		Vojtechdobes\PHPStan\Dibi\DatabaseType $databaseType,
	): Dibi\Connection
	{
		$config = Nette\Neon\Neon::decodeFile(__DIR__ . '/DatabaseSpecificTypeInferenceTest.extension.neon');

		return new Dibi\Connection($config['parameters']['databaseCredentials'][$databaseType->value]);
	}

}
