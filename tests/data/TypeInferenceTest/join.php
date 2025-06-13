<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;


$dibi = new Dibi\Connection([
	'driver' => 'pdo',
	'dsn' => 'sqlite::memory:',
]);


assertType(
	'Dibi\Row<array{book_name: string, author_name: string|null}>|null',
	$dibi->query('SELECT `b`.`name` `book_name`, `a`.`name` `author_name` FROM `books` `b` LEFT JOIN `authors_x_books` `axb` ON `axb`.`book_id` = `b`.`id` LEFT JOIN `authors` `a` ON `a`.`id` = `axb`.`author_id`')->fetch(),
);

assertType(
	'Dibi\Row<array{book_name: string, author_name: string}>|null',
	$dibi->query('SELECT `b`.`name` `book_name`, `a`.`name` `author_name` FROM `books` `b` INNER JOIN `authors_x_books` `axb` ON `axb`.`book_id` = `b`.`id` INNER JOIN `authors` `a` ON `a`.`id` = `axb`.`author_id`')->fetch(),
);
