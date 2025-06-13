<?php declare(strict_types=1);

namespace Vojtechdobes\Tests\DatabaseSpecificTypeInference;

use Dibi;
use Vojtechdobes\Tests\DatabaseSpecificTypeInferenceSharedBase;
use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;


final class ConstantBooleanTest extends DatabaseSpecificTypeInferenceSharedBase
{

	private const Query = 'SELECT true `x`';



	public function withMariadb(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => 1]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'non-empty-list<Dibi\Row<array{x: 1}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withMysql(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => 1]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'non-empty-list<Dibi\Row<array{x: 1}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withPostgres(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => true]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'non-empty-list<Dibi\Row<array{x: true}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}



	public function withSqlite(Dibi\Connection $dibi): void
	{
		self::assertSameRows(
			$expectedRows = [
				new Dibi\Row(['x' => 1]),
			],
			$actualRows = $dibi->query(self::Query)->fetchAll(),
		);

		$expectedType = 'non-empty-list<Dibi\Row<array{x: 1}>>';

		assertType($expectedType, $actualRows);
		assertSuperType($expectedType, $expectedRows);
	}

}
