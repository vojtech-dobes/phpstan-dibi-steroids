<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'list<Dibi\Row<array{author_name: string, book_name: string}>>',
	$dibi->query('SELECT `a`.`name` `author_name`, `b`.`name` `book_name` FROM `authors` `a`, `books` `b`')->fetchAll(),
);
