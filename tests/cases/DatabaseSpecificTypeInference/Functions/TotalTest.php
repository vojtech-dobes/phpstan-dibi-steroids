<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference\Functions;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class TotalTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = 'SELECT TOTAL(`cost`) FROM `books`';



	public function withMariadb(Dibi\Connection $dibi): void
	{
		$this->expectSqlSyntaxError();

		assertType(
			"list<Dibi\Row<array{'TOTAL(`cost`)': mixed}>>",
			$dibi->query(self::Query)->fetchAll(),
		);
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		$this->expectSqlSyntaxError();

		assertType(
			"list<Dibi\Row<array{'TOTAL(`cost`)': mixed}>>",
			$dibi->query(self::Query)->fetchAll(),
		);
	}



	public function withPostgres(Dibi\Connection $dibi): void
	{
		$this->expectSqlSyntaxError();

		assertType(
			'list<Dibi\Row<array{\'TOTAL("cost")\': mixed}>>',
			$dibi->query(self::Query)->fetchAll(),
		);
	}



	public function withSqlite(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['TOTAL([cost])' => 10.0]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "list<Dibi\Row<array{'TOTAL([cost])': float}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}

}
