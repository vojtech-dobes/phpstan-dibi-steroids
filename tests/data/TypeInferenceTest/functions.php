<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	"'ab'",
	$dibi->query('SELECT CONCAT("a", "b")')->fetchSingle(),
);

assertType(
	"'ab'",
	$dibi->query('SELECT (CONCAT("a", "b"))')->fetchSingle(),
);
