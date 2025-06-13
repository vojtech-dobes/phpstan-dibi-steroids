<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc(''), // same as fetchAll
);

assertType(
	'array<int, Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc('id'),
);

assertType(
	'array<string, Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc('name'),
);

assertType(
	'array<int, non-empty-list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc('year[]'),
);

assertType(
	'array<int, Dibi\Row<array{id: int, name: array<string, Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc('year->name'),
);

assertType(
	'array<int, string>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc('id=name'),
);

assertType(
	'list<string>',
	$dibi->query('SELECT * FROM `books`')->fetchAssoc('[]=name'),
);
