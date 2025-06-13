<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	"'SELECT * FROM [books]'",
	$dibi->translate('SELECT *', $dibi::expression('FROM %n', 'books')),
);

assertType(
	"'SELECT * FROM `books`'",
	$dibi->translate('SELECT *', $dibi::literal('FROM `books`')),
);

assertType(
	"'INSERT INTO [books] ([name], [year]) VALUES (\'Lord of the Rings: The Fellowship of the Ring\', 1954)'",
	$dibi->translate('INSERT INTO `books`', [
		'name' => 'Lord of the Rings: The Fellowship of the Ring',
		'year' => $dibi::expression('%i', 1954),
	]),
);

static function (AbcEnum $table) use ($dibi): void {
	assertType(
		"'SELECT * FROM [a]'|'SELECT * FROM [b]'|'SELECT * FROM [c]'",
		$dibi->translate('SELECT *', $dibi::expression('FROM %n', $table)),
	);
};

/**
 * @param 'foo'|'bar' $table
 */
function loadFromExpressionTable(Dibi\Connection $dibi, string $table): void {
	assertType(
		"'SELECT * FROM [bar]'|'SELECT * FROM [foo]'",
		$dibi->translate('SELECT *', $dibi::expression('FROM %n', $table)),
	);
}

/**
 * @param '[foo]'|'[bar]' $table
 */
function loadFromLiteralTable(Dibi\Connection $dibi, string $table): void {
	assertType(
		"'SELECT * FROM [bar]'|'SELECT * FROM [foo]'",
		$dibi->translate('SELECT * FROM', $dibi::literal($table)),
	);
}
