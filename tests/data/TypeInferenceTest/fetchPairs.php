<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'array<int, string>', // id => name
	$dibi->query('SELECT * FROM `books`')->fetchPairs(),
);

assertType(
	'array<int, string>', // id => name
	$dibi->query('SELECT `id`, `name` FROM `books`')->fetchPairs(),
);

assertType(
	'list<int>',
	$dibi->query('SELECT `id` FROM `books`')->fetchPairs(),
);

assertType(
	'list<string>',
	$dibi->query('SELECT `name` FROM `books`')->fetchPairs(),
);

assertType(
	'array<int, string>',
	$dibi->query('SELECT `id`, `name` FROM `books`')->fetchPairs('id', 'name'),
);

assertType(
	'array<string, int>',
	$dibi->query('SELECT `id`, `name` FROM `books`')->fetchPairs('name', 'id'),
);

// shortcut

assertType(
	'array<int, string>', // id => name
	$dibi->fetchPairs('SELECT * FROM `books`'),
);

assertType(
	'list<int>',
	$dibi->fetchPairs('SELECT `id` FROM `books`'),
);
