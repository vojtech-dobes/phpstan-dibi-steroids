<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference\Functions;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class TrimColumnNullableTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = 'SELECT TRIM(`notes`) `notes` FROM `books` LIMIT 1';



	public function withMariadb(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['notes' => null]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{notes: string|null}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['notes' => null]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{notes: string|null}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withPostgres(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['notes' => '']),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{notes: string|null}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withSqlite(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['notes' => null]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'list<Dibi\Row<array{notes: string|null}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}

}
