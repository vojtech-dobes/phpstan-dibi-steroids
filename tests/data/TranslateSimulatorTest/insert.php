<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	"'INSERT INTO [books] ([name], [year]) VALUES (\'Lord of the Rings: The Fellowship of the Ring\', 1954)'",
	$dibi->translate('INSERT INTO `books`', [
		'name' => 'Lord of the Rings: The Fellowship of the Ring',
		'year' => 1954,
	]),
);

assertType(
	"'INSERT INTO [books] ([name], [year]) VALUES (\'Lord of the Rings: The Fellowship of the Ring\', 1954) , (\'Lord of the Rings: The Two Towers\', 1954) , (\'Lord of the Rings: The Return of the King\', 1955)'",
	$dibi->translate('INSERT INTO `books`', [
		'name' => 'Lord of the Rings: The Fellowship of the Ring',
		'year' => 1954,
	], [
		'name' => 'Lord of the Rings: The Two Towers',
		'year' => 1954,
	], [
		'name' => 'Lord of the Rings: The Return of the King',
		'year' => 1955,
	]),
);

assertType(
	"'INSERT INTO [books] ([name], [year]) VALUES (\'Lord of the Rings: The Fellowship of the Ring\', 1954) , (\'Lord of the Rings: The Two Towers\', 1954) , (\'Lord of the Rings: The Return of the King\', 1955)'",
	$dibi->translate('INSERT INTO `books`', ...[
		[
			'name' => 'Lord of the Rings: The Fellowship of the Ring',
			'year' => 1954,
		],
		[
			'name' => 'Lord of the Rings: The Two Towers',
			'year' => 1954,
		],
		[
			'name' => 'Lord of the Rings: The Return of the King',
			'year' => 1955,
		],
	]),
);

static function (string $name, int $year) use ($dibi): void {
	assertType(
		"'INSERT INTO [books] ([name], [year]) VALUES (\'phpstan_generated_string\', 67274)'",
		$dibi->translate('INSERT INTO `books`', [
			'name' => $name,
			'year' => $year,
		]),
	);
};

/**
 * @param array<string, mixed> $values
 */
function insertGenericColumns(Dibi\Connection $dibi, array $values): void {
	assertType(
		"'INSERT INTO [books] /* generic array */'",
		$dibi->translate('INSERT INTO `books`', $values),
	);
}

/**
 * @param array<'name', string> $values
 */
function insertGenericallyKnownColumns(Dibi\Connection $dibi, array $values): void {
	assertType(
		"'INSERT INTO [books] ([name]) VALUES (\'phpstan_generated_string\')'",
		$dibi->translate('INSERT INTO `books`', $values),
	);
}

/**
 * @param array{
 *   name: string,
 *   year: int,
 * } $values
 */
function insertSpecificColumns(Dibi\Connection $dibi, array $values): void {
	assertType(
		"'INSERT INTO [books] ([name], [year]) VALUES (\'phpstan_generated_string\', 67274)'",
		$dibi->translate('INSERT INTO `books`', $values),
	);
}
