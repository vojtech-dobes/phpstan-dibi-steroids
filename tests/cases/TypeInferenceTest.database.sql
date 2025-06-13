CREATE TABLE `authors` (
	`id` INTEGER PRIMARY KEY,
	`name` TEXT NOT NULL
) STRICT;

CREATE TABLE `books` (
	`id` INTEGER PRIMARY KEY,
	`name` TEXT NOT NULL,
	`year` INTEGER NOT NULL,
	`notes` TEXT,
	`is_available` BOOLEAN NOT NULL
) STRICT;

CREATE TABLE `books_x_authors` (
	`author_id` INTEGER NOT NULL,
	`book_id` INTEGER NOT NULL
) STRICT;

INSERT INTO `books` (`name`, `year`, `is_available`) VALUES
('Lord of the Rings: The Fellowship of the Ring', 1954, 1),
('Lord of the Rings: The Two Towers', 1954, 0),
('Lord of the Rings: The Return of the King', 1955, 1);

CREATE VIEW `available_books` AS
SELECT *
FROM `books`
WHERE `is_available` = 1;
