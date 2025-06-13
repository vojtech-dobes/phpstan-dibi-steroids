<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


array_map(
	static function (Dibi\Row $row): void {
		assertType(
			'Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>',
			$row,
		);
	},
	$dibi->query('SELECT * FROM `books`')->fetchAll(),
);
