<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class SelectFromValuesTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = 'SELECT * FROM (VALUES (1), (3), (5)) `t`';



	public function withMariadb(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row([1 => 1]),
				new Dibi\Row([1 => 3]),
				new Dibi\Row([1 => 5]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{1: 1|3|5}>>';

		assertType($expectedType, $actualRows);
		assertSuperType('mixed', 'ahoj');
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		$this->expectSqlSyntaxError();

		assertType(
			'list<Dibi\Row<array<string, mixed>>>',
			$dibi->query(self::Query)->fetchAll(),
		);
	}



	public function withPostgres(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['column1' => 1]),
				new Dibi\Row(['column1' => 3]),
				new Dibi\Row(['column1' => 5]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{column1: 1|3|5}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withSqlite(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['column1' => 1]),
				new Dibi\Row(['column1' => 3]),
				new Dibi\Row(['column1' => 5]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{column1: 1|3|5}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}

}
