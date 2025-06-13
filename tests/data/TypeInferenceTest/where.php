<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{id: int, notes: string}>>',
	$dibi->query('SELECT `id`, `notes` FROM `books` WHERE `notes` IS NOT NULL')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int, notes: null}>>',
	$dibi->query('SELECT `id`, `notes` FROM `books` WHERE `notes` IS NULL')->fetchAll(),
);

static function (int $id) use ($dibi): void {
	assertType(
		'Dibi\Row<array{id: int}>|null',
		$dibi->query('SELECT `id` FROM `books` WHERE `id` = %i', $id)->fetch(),
	);
};

assertType(
	'Dibi\Row<array{id: 1}>|null',
	$dibi->query('SELECT `id` FROM `books` WHERE `id` = %i', 1)->fetch(),
);

assertType(
	'list<Dibi\Row<array{id: int, is_available: 1}>>',
	$dibi->query('SELECT `id`, `is_available` FROM `books` WHERE `is_available` = %b', true)->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int, is_available: 0}>>',
	$dibi->query('SELECT `id`, `is_available` FROM `books` WHERE `is_available` = %b', false)->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int, is_available: 0}>>',
	$dibi->query('SELECT `id`, `is_available` FROM `books` WHERE `is_available` != %b', true)->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int, is_available: 1}>>',
	$dibi->query('SELECT `id`, `is_available` FROM `books` WHERE `is_available` != %b', false)->fetchAll(),
);

static function (string $notes) use ($dibi): void {
	assertType(
		'Dibi\Row<array{id: int, notes: string}>|null',
		$dibi->query('SELECT `id`, `notes` FROM `books` WHERE `notes` = %s', $notes)->fetch(),
	);
};

static function (?string $notes) use ($dibi): void {
	assertType(
		'Dibi\Row<array{id: int, notes: string}>|null',
		$dibi->query('SELECT `id`, `notes` FROM `books` WHERE `notes` = %s', $notes)->fetch(),
	);
};

static function (string $notes) use ($dibi): void {
	assertType(
		'Dibi\Row<array{id: int, notes: string}>|null',
		$dibi->query('SELECT `id`, `notes` FROM `books` WHERE `notes` IS %s', $notes)->fetch(),
	);
};

static function (?string $notes) use ($dibi): void {
	assertType(
		'Dibi\Row<array{id: int, notes: string|null}>|null',
		$dibi->query('SELECT `id`, `notes` FROM `books` WHERE `notes` IS %s', $notes)->fetch(),
	);
};

static function (bool $switch) use ($dibi): void {
	assertType(
		'list<Dibi\Row<array{id: int, notes: string|null, is_available: 0|1}>>',
		$dibi->query('SELECT `id`, `notes`, `is_available` FROM `books` WHERE %if', $switch, '`is_available` = %b', true, '%else `notes` IS NOT NULL%end')->fetchAll(),
	); // poor result due to https://phpstan.org/r/be6692d2-39f9-4ceb-8921-e031618700b8
};

assertType(
	'list<Dibi\Row<array{id: int, is_available: 1, notes: string}>>',
	$dibi->query('SELECT `id`, `is_available`, `notes` FROM `books` WHERE `is_available` = %b', true, ' AND `notes` IS NOT NULL')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{id: int, is_available: 1, notes: string}>>',
	$dibi->query('SELECT `id`, `is_available`, `notes` FROM `books` WHERE (`is_available` = %b', true, ' AND `notes` IS NOT NULL)')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{is_available: 1}>>',
	$dibi->query('SELECT `is_available` FROM `books` WHERE `is_available` IN (1)')->fetchAll(),
);

assertType(
	"list<Dibi\Row<array{name: 'Alice'|'Bob'}>>",
	$dibi->query('SELECT `name` FROM `books` WHERE `name` IN ("Alice", "Bob")')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{is_available: 0}>>',
	$dibi->query('SELECT `is_available` FROM `books` WHERE `is_available` NOT IN (1)')->fetchAll(),
);
