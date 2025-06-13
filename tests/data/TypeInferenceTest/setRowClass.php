<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'Dibi\Row<array{id: int}>|null',
	$dibi->query('SELECT `id` FROM `books`')->setRowClass(Dibi\Row::class)->fetch(),
);

assertType(
	'array{id: int}|null',
	$dibi->query('SELECT `id` FROM `books`')->setRowClass(null)->fetch(),
);

assertType(
	'CustomRow<array{id: int}>|null',
	$dibi->query('SELECT `id` FROM `books`')->setRowClass(CustomRow::class)->fetch(),
);

assertType(
	'SecondCustomRow<array{id: int}>|null',
	$dibi->query('SELECT `id` FROM `books`')->setRowClass(CustomRow::class)->setRowClass(SecondCustomRow::class)->fetch(),
);

assertType(
	'array{id: int}|null',
	$dibi->query('SELECT `id` FROM `books`')->setRowClass(CustomRow::class)->setRowClass(null)->fetch(),
);
