<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'CustomRow<array{id: int}>|null',
	$dibi->query('SELECT `id` FROM `books`')->setRowFactory(
		static fn ($row) => new CustomRow($row->toArray()),
	)->fetch(),
);
