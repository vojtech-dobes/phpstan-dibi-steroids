<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'non-empty-list<Dibi\Row<array{column1: 1|2|3}>>',
	$dibi->query('VALUES (1), (2), (3)')->fetchAll(),
);

assertType(
	'list<Dibi\Row<array{column1: 1|2|3}>>',
	$dibi->query('SELECT * FROM (VALUES (1), (2), (3)) `t`')->fetchAll(),
);

assertType(
	"list<Dibi\Row<array{column1: 'Alice'|'Bob'|'Charlie'}>>",
	$dibi->query('SELECT * FROM (VALUES ((SELECT "Alice")), ((SELECT "Bob")), ((SELECT "Charlie"))) `t`')->fetchAll(),
);
