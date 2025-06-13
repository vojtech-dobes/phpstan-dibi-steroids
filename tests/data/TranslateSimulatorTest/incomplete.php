<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


static function (Dibi\Expression $expression) use ($dibi): void {
	assertType(
		"'SELECT * FROM [books] /* generic Dibi\\\Expression */'",
		$dibi->translate('SELECT * FROM `books` ?', $expression),
	);
};

static function (Dibi\Literal $literal) use ($dibi): void {
	assertType(
		"'SELECT * FROM /* generic Dibi\\\Literal */'",
		$dibi->translate('SELECT * FROM ?', $literal),
	);
};

static function (Dibi\Expression $expression) use ($dibi): void {
	assertType(
		"'SELECT /* generic Dibi\\\Expression */ FROM [books]'",
		$dibi->translate('SELECT ? FROM `books`', $expression),
	);
};

static function (Dibi\Literal $literal) use ($dibi): void {
	assertType(
		"'SELECT /* generic Dibi\\\Literal */ FROM [books]'",
		$dibi->translate('SELECT ? FROM `books`', $literal),
	);
};
