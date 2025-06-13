<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	"'SELECT * FROM [books]'",
	$dibi->translate('SELECT * FROM `books`'),
);

assertType(
	"'SELECT * FROM [books]'",
	$dibi->translate('SELECT * FROM %n', 'books'),
);

static function (string $table) use ($dibi): void {
	assertType(
		"'SELECT * FROM [phpstan_generated_string]'",
		$dibi->translate('SELECT * FROM %n', $table),
	);
};

/**
 * @param "foo"|"bar" $table
 */
function loadFromSpecificTable(Dibi\Connection $dibi, string $table): void {
	assertType(
		"'SELECT * FROM [bar]'|'SELECT * FROM [foo]'",
		$dibi->translate('SELECT * FROM %n', $table),
	);
}

assertType(
	"'SELECT * FROM [books]'",
	$dibi->translate('SELECT * FROM %n', new StringableName('books')),
);

static function (mixed $table) use ($dibi): void {
	assertType(
		'string',
		$dibi->translate('SELECT * FROM %n', $table),
	);
};

static function (AbcEnum $table) use ($dibi): void {
	assertType(
		"'SELECT * FROM [a]'|'SELECT * FROM [b]'|'SELECT * FROM [c]'",
		$dibi->translate('SELECT * FROM %n', $table),
	);
};

static function (AbcEnum $column) use ($dibi): void {
	assertType(
		"'SELECT [a] FROM [books]'|'SELECT [b] FROM [books]'|'SELECT [c] FROM [books]'",
		$dibi->translate('SELECT %n FROM `books`', $column),
	);
};
