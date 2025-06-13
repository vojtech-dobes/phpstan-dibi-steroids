<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	"'SELECT * FROM [books] WHERE [year] = 1955'",
	$dibi->translate('SELECT * FROM `books` WHERE `year` = %i', 1955),
);

static function (int $year) use ($dibi): void {
	assertType(
		"'SELECT * FROM [books] WHERE [year] = 67274'",
		$dibi->translate('SELECT * FROM `books` WHERE `year` = %i', $year),
	);
};
