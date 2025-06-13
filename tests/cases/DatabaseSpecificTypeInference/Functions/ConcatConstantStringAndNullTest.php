<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference\Functions;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class ConcatConstantStringAndNullTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = "SELECT CONCAT('Alice', null) `x`";



	public function withMariadb(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => null]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'non-empty-list<Dibi\Row<array{x: null}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => null]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'non-empty-list<Dibi\Row<array{x: null}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withPostgres(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => 'Alice']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "non-empty-list<Dibi\Row<array{x: 'Alice'}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withSqlite(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => 'Alice']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = "non-empty-list<Dibi\Row<array{x: 'Alice'}>>";

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}

}
