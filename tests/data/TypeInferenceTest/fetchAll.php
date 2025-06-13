<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->query('SELECT * FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int}>>',
	$dibi->query('SELECT `id` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->query('SELECT `name` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{year: int}>>',
	$dibi->query('SELECT `year` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{notes: string|null}>>',
	$dibi->query('SELECT `notes` FROM `books`')->fetchAll(),
);

assertType(
	'non-empty-list<Dibi\Row<array{total: int<0, max>}>>',
	$dibi->query('SELECT count(*) `total` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{total: int<0, max>}>>',
	$dibi->query('SELECT count(*) `total` FROM `books` GROUP BY `year`')->fetchAll(),
);

// shortcut

assertType(
	'list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->fetchAll('SELECT * FROM `books`'),
);
