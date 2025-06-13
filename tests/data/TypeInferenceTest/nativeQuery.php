<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->nativeQuery('SELECT * FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int}>>',
	$dibi->nativeQuery('SELECT `id` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->nativeQuery('SELECT `name` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{year: int}>>',
	$dibi->nativeQuery('SELECT `year` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{notes: string|null}>>',
	$dibi->nativeQuery('SELECT `notes` FROM `books`')->fetchAll(),
);

assertType(
	'non-empty-list<Dibi\Row<array{total: int<0, max>}>>',
	$dibi->nativeQuery('SELECT count(*) `total` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{total: int<0, max>}>>',
	$dibi->nativeQuery('SELECT count(*) `total` FROM `books` GROUP BY `year`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: *ERROR*}>>',
	$dibi->nativeQuery('SELECT `id` FROM ?', $dibi::expression('`books`'))->fetchAll(),
);
