<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'Dibi\Row<array{name: string}>|null',
	$dibi->query('SELECT `name` FROM (SELECT * FROM `books`)')->fetch(),
);

assertType(
	'Dibi\Row<array{name: string, year: int}>|null',
	$dibi->query('SELECT `x`.`name`, `y`.`year` FROM (SELECT `name` FROM `books`) `x` INNER JOIN (SELECT `year` FROM `books`) `y` ON 1=1')->fetch(),
);

assertType(
	'Dibi\Row<array{name: string, year: int}>|null',
	$dibi->query('SELECT * FROM (SELECT `name`, `year` FROM `books`)')->fetch(),
);
