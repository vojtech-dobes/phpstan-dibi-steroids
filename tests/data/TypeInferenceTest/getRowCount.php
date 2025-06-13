<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'int<0, max>',
	$dibi->query('SELECT `id` FROM `books`')->getRowCount(),
);

assertType(
	'int<0, max>',
	$dibi->query('SELECT * FROM `books`')->getRowCount(),
);

assertType(
	'int<1, max>',
	$dibi->query('SELECT COUNT(*) FROM `books`')->getRowCount(),
);


assertType(
	'int<0, max>',
	$dibi->query('SELECT `id` FROM `books`')->count(),
);

assertType(
	'int<0, max>',
	$dibi->query('SELECT * FROM `books`')->count(),
);

assertType(
	'int<1, max>',
	$dibi->query('SELECT COUNT(*) FROM `books`')->count(),
);


assertType(
	'int<0, max>',
	count($dibi->query('SELECT `id` FROM `books`')),
);

assertType(
	'int<0, max>',
	count($dibi->query('SELECT * FROM `books`')),
);

assertType(
	'int<1, max>',
	count($dibi->query('SELECT COUNT(*) FROM `books`')),
);
