<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->query('WITH `cte` AS (SELECT * FROM `books`) SELECT `name` FROM `cte`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->query('WITH `cte1` AS (SELECT * FROM `books`), `cte2` AS (SELECT * FROM `cte1`) SELECT `name` FROM `cte2`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>>',
	$dibi->query('WITH `cte1` AS (SELECT * FROM `books`), `cte2` AS (SELECT * FROM `cte1`) SELECT * FROM `cte2`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: mixed}>>',
	$dibi->query('WITH `cte2` AS (SELECT * FROM `cte1`), `cte1` AS (SELECT * FROM `books`) SELECT `name` FROM `cte2`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array<string, mixed>>>',
	$dibi->query('WITH `cte2` AS (SELECT * FROM `cte1`), `cte1` AS (SELECT * FROM `books`) SELECT * FROM `cte2`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: mixed}>>',
	$dibi->query('WITH `cte` AS (SELECT * FROM `cte`), `cte` AS (SELECT * FROM `books`) SELECT `name` FROM `cte`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->query('WITH `books` AS (SELECT `id` FROM `books`) SELECT `name` FROM `books`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->query('WITH `cte` AS (WITH `cte` AS (SELECT * FROM `books`) SELECT * FROM `cte`) SELECT `name` FROM `cte`')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{name: string}>>',
	$dibi->query('WITH `cte2` AS (WITH `cte1` AS (SELECT * FROM `books`) SELECT * FROM `cte1`) SELECT `name` FROM `cte2`')->fetchAll(),
);
