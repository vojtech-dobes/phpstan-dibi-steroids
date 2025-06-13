<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);

foreach ($dibi->query('SELECT * FROM `books`') as $row) {
	assertType(
		'Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>',
		$row,
	);
}
