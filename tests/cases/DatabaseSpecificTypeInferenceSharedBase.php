<?php declare(strict_types=1);

namespace Vojtechdobes\Tests;

use Dibi;
use Nette;
use PHPUnit;


abstract class DatabaseSpecificTypeInferenceSharedBase extends PHPUnit\Framework\TestCase
{

	/** @var Dibi\Connection<'mariadb'> */
	protected static Dibi\Connection $mariadb;

	/** @var Dibi\Connection<'mariadb'> */
	protected static Dibi\Connection $mysql;

	/** @var Dibi\Connection<'mariadb'> */
	protected static Dibi\Connection $postgres;

	/** @var Dibi\Connection<'mariadb'> */
	protected static Dibi\Connection $sqlite;



	public static function setUpBeforeClass(): void
	{
		$config = Nette\Neon\Neon::decodeFile(__DIR__ . '/DatabaseSpecificTypeInferenceTest.extension.neon');

		foreach (self::dataDatabases() as [$database]) {
			self::${$database} = new Dibi\Connection($config['parameters']['databaseCredentials'][$database]);
			self::${$database}->loadFile(__DIR__ . '/' . $database . '.sql');
		}
	}



	/**
	 * @return list<array{string}>
	 */
	public static function dataDatabases(): iterable
	{
		return [
			['mariadb'],
			['mysql'],
			['postgres'],
			['sqlite'],
		];
	}



	#[PHPUnit\Framework\Attributes\DataProvider('dataDatabases')]
	public function testMain(string $database): void
	{
		$this->{'with' . ucfirst($database)}(self::${$database});
	}



	/**
	 * @param Dibi\Connection<'mariadb'> $dibi
	 */
	abstract public function withMariadb(Dibi\Connection $dibi): void;



	/**
	 * @param Dibi\Connection<'mysql'> $dibi
	 */
	abstract public function withMysql(Dibi\Connection $dibi): void;



	/**
	 * @param Dibi\Connection<'postgres'> $dibi
	 */
	abstract public function withPostgres(Dibi\Connection $dibi): void;



	/**
	 * @param Dibi\Connection<'sqlite'> $dibi
	 */
	abstract public function withSqlite(Dibi\Connection $dibi): void;



	protected function expectSqlSyntaxError(): void
	{
		$this->expectException(Dibi\Exception::class);
		$this->expectExceptionMessageMatches('/error .* SQL syntax|syntax error|function .* does not exist/i');
	}



	/**
	 * @param list<Dibi\Row> $expected
	 * @param list<Dibi\Row> $actual
	 */
	final protected static function assertSameRows(
		array $expected,
		array $actual,
	): void
	{
		self::assertSame(
			print_r($expected, true),
			print_r($actual, true),
		);
	}

}
