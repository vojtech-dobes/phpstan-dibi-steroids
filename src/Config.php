<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use Dibi;
use LogicException;
use PHPSQLParser;
use PHPStan;


final class Config
{

	/** @var array<string, Database> */
	private array $databaseObjects = [];



	/**
	 * @param array<string, Dibi\Connection> $databases
	 */
	public function __construct(
		public readonly ?array $databases,
		public readonly ?Dibi\Connection $database,
		public readonly string $generatedDir,
	)
	{
		if (($this->database !== null xor $this->databases !== null) === false) {
			throw new LogicException(
				"Either 'dibi.database' or 'dibi.databases' must be specified",
			);
		}
	}



	/**
	 * @return list<string>
	 */
	public function listDatabases(): array
	{
		if ($this->databases !== null) {
			return array_keys($this->databases);
		}

		return ['main'];
	}



	public function getDatabase(string $databaseName): Database
	{
		$connection = (
			$this->databases[$databaseName]
			?? ($databaseName === 'main' ? $this->database : null)
			?? throw new LogicException("Database '{$databaseName}' isn't configured")
		);

		$reflectorClass = $connection
			->getDriver()
			->getReflector()::class;

		$databaseType = match ($reflectorClass) {
			Dibi\Drivers\MySqlReflector::class => str_contains($connection->fetchSingle('SELECT VERSION()'), 'MariaDB')
				? DatabaseType::Mariadb
				: DatabaseType::Mysql,
			Dibi\Drivers\PostgreReflector::class => DatabaseType::Postgres,
			Dibi\Drivers\SqliteReflector::class => DatabaseType::Sqlite,
			default => throw new PHPStan\ShouldNotHappenException(),
		};

		$this->databaseObjects[$databaseName] ??= new Database(
			connection: $connection,
			name: $databaseName,
			sqlParser: new PHPSQLParser\PHPSQLParser(
				false,
				false,
				$databaseType->getSqlParserOptions(),
			),
			translateSimulator: new TranslateSimulator($connection),
			type: $databaseType,
		);

		return $this->databaseObjects[$databaseName];
	}

}
