<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'int|null',
	$dibi->query('SELECT * FROM `books`')->fetchSingle(),
);

assertType(
	'int|null',
	$dibi->query('SELECT `id` FROM `books`')->fetchSingle(),
);

assertType(
	'string|null',
	$dibi->query('SELECT `name` FROM `books`')->fetchSingle(),
);

assertType(
	'int|null',
	$dibi->query('SELECT `year` FROM `books`')->fetchSingle(),
);

assertType(
	'string|null',
	$dibi->query('SELECT `notes` FROM `books`')->fetchSingle(),
);

assertType(
	'int<0, max>',
	$dibi->query('SELECT count(*) `total` FROM `books`')->fetchSingle(),
);

assertType(
	'int<0, max>|null',
	$dibi->query('SELECT count(*) `total` FROM `books` GROUP BY `year`')->fetchSingle(),
);

// shortcut

assertType(
	'int|null',
	$dibi->fetchSingle('SELECT * FROM `books`'),
);
