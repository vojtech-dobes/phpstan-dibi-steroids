<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference\Functions;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class TrimRemoveStringClassicStyleTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = "SELECT TRIM('-Alice-', '-')";



	public function withMariadb(Dibi\Connection $dibi): void
	{
		$this->expectSqlSyntaxError();

		assertType(
			"non-empty-list<Dibi\Row<array{\"TRIM('-Alice-', '-')\": 'Alice'}>>",
			$dibi->query(self::Query)->fetchAll(),
		);
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		$this->expectSqlSyntaxError();

		assertType(
			"non-empty-list<Dibi\Row<array{\"TRIM('-Alice-', '-')\": 'Alice'}>>",
			$dibi->query(self::Query)->fetchAll(),
		);
	}



	public function withPostgres(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['btrim' => 'Alice']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "non-empty-list<Dibi\Row<array{btrim: 'Alice'}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withSqlite(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(["TRIM('-Alice-', '-')" => 'Alice']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "non-empty-list<Dibi\Row<array{\"TRIM('-Alice-', '-')\": 'Alice'}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}

}
