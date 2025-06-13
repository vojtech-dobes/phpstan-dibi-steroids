<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


static function (Dibi\Expression $expression) use ($dibi): void {
	assertType(
		'list<Dibi\Row<array{name: string}>>',
		$dibi->query('SELECT `name` FROM `books` ?', $expression)->fetchAll(),
	);
};

static function (Dibi\Literal $literal) use ($dibi): void {
	assertType(
		'list<Dibi\Row<array<string, mixed>>>',
		$dibi->query('SELECT * FROM ?', $literal)->fetchAll(),
	);
};

static function (Dibi\Expression $expression) use ($dibi): void {
	assertType(
		'list<Dibi\Row<array<string, mixed>>>',
		$dibi->query('SELECT ? FROM `books`', $expression)->fetchAll(),
	);
};

static function (Dibi\Literal $literal) use ($dibi): void {
	assertType(
		'list<Dibi\Row<array<string, mixed>>>',
		$dibi->query('SELECT ? FROM `books`', $literal)->fetchAll(),
	);
};

static function (string $tableName) use ($dibi): void {
	assertType(
		'list<Dibi\Row<array<string, mixed>>>',
		$dibi->query('SELECT * FROM %n', $tableName)->fetchAll(),
	);
};
