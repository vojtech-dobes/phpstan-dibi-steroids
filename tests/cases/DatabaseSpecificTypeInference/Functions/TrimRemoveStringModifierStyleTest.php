<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference\Functions;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class TrimRemoveStringModifierStyleTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = "SELECT TRIM('-' FROM '-Alice-')";



	public function withMariadb(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(["TRIM('-' FROM '-Alice-')" => 'Alice']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "non-empty-list<Dibi\Row<array{\"TRIM('-' FROM '-Alice-')\": 'Alice'}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(["TRIM('-' FROM '-Alice-')" => 'Alice']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "non-empty-list<Dibi\Row<array{\"TRIM('-' FROM '-Alice-')\": 'Alice'}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
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
		$this->expectSqlSyntaxError();

		assertType(
			"non-empty-list<Dibi\Row<array{\"TRIM('-' FROM '-Alice-')\": mixed}>>",
			$dibi->query(self::Query)->fetchAll(),
		);
	}

}
