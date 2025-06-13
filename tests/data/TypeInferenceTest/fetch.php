<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>|null',
	$dibi->query('SELECT * FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{id: int}>|null',
	$dibi->query('SELECT `id` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{name: string}>|null',
	$dibi->query('SELECT `name` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{year: int}>|null',
	$dibi->query('SELECT `year` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{notes: string|null}>|null',
	$dibi->query('SELECT `notes` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{total: int<0, max>}>',
	$dibi->query('SELECT count(*) `total` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{total: int<0, max>}>|null',
	$dibi->query('SELECT count(*) `total` FROM `books` GROUP BY `year`')->fetch(),
);

// shortcut

assertType(
	'Dibi\Row<array{id: int, name: string, year: int, notes: string|null, is_available: 0|1}>|null',
	$dibi->fetch('SELECT * FROM `books`'),
);
