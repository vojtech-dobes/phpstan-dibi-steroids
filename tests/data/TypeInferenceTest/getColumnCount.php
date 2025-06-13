<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'1',
	$dibi->query('SELECT `id` FROM `books`')->getColumnCount(),
);

assertType(
	'2',
	$dibi->query('SELECT `id`, `name` FROM `books`')->getColumnCount(),
);

assertType(
	'5',
	$dibi->query('SELECT * FROM `books`')->getColumnCount(),
);
