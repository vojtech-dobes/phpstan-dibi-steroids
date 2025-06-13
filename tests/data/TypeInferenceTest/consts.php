<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);

assertType(
	'5',
	$dibi->query('SELECT 5')->fetchSingle(),
);

assertType(
	'5.5',
	$dibi->query('SELECT 5.5')->fetchSingle(),
);

assertType(
	"'a'",
	$dibi->query('SELECT "a"')->fetchSingle(),
);

assertType(
	"'a'",
	$dibi->query("SELECT 'a'")->fetchSingle(),
);

assertType(
	"'A'",
	$dibi->query('SELECT "A"')->fetchSingle(),
);

assertType(
	'null',
	$dibi->query('SELECT null')->fetchSingle(),
);

// magic constants

assertType(
	'float',
	$dibi->query('SELECT 6.7274')->fetchSingle(),
);

assertType(
	'int',
	$dibi->query('SELECT 67274')->fetchSingle(),
);

assertType(
	'string',
	$dibi->query('SELECT "phpstan_generated_string"')->fetchSingle(),
);
