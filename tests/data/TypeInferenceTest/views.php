<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 1}>>',
	$dibi->query('SELECT * FROM `available_books`')->fetchAll(),
);
