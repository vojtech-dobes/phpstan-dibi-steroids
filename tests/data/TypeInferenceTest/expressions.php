<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'Dibi\Row<array{x: 0|1}>|null',
	$dibi->query('SELECT `notes` IS NULL `x` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{x: 0|1}>|null',
	$dibi->query('SELECT `notes` IS NOT NULL `x` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{x: null}>|null',
	$dibi->query('SELECT `notes` = NULL `x` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{x: null}>|null',
	$dibi->query('SELECT `notes` != NULL `x` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{x: 0|1|null}>|null',
	$dibi->query('SELECT `notes` = `description` `x` FROM `books`')->fetch(),
);

assertType(
	'Dibi\Row<array{x: 0|1|null}>|null',
	$dibi->query('SELECT `notes` != `description` `x` FROM `books`')->fetch(),
);

assertType(
	'1',
	$dibi->query('SELECT NULL IS NULL')->fetchSingle(),
);

assertType(
	'0',
	$dibi->query('SELECT NULL IS NOT NULL')->fetchSingle(),
);

assertType(
	'0',
	$dibi->query('SELECT 1 IS NULL')->fetchSingle(),
);

assertType(
	'1',
	$dibi->query('SELECT 1 IS NOT NULL')->fetchSingle(),
);

assertType(
	'null',
	$dibi->query('SELECT NULL = NULL')->fetchSingle(),
);

assertType(
	'null',
	$dibi->query('SELECT NULL != NULL')->fetchSingle(),
);

assertType(
	'null',
	$dibi->query('SELECT 1 = NULL')->fetchSingle(),
);

assertType(
	'null',
	$dibi->query('SELECT 1 != NULL')->fetchSingle(),
);
