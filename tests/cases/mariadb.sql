DROP TABLE IF EXISTS `authors`;
CREATE TABLE `authors` (
	`id` BIGINT AUTO_INCREMENT PRIMARY KEY,
	`name` TEXT NOT NULL
);

DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
	`id` BIGINT AUTO_INCREMENT PRIMARY KEY,
	`name` TEXT NOT NULL,
	`year` SMALLINT NOT NULL,
	`notes` TEXT,
	`is_available` TINYINT(1) NOT NULL,
	`cost` DECIMAL(10,2) NULL
);

DROP TABLE IF EXISTS `books_x_authors`;
CREATE TABLE `books_x_authors` (
	`author_id` BIGINT NOT NULL,
	`book_id` BIGINT NOT NULL
);

INSERT INTO `authors` (`name`) VALUES
('J. R. R. Tolkien');

INSERT INTO `books` (`name`, `year`, `is_available`, `cost`) VALUES
('Lord of the Rings: The Fellowship of the Ring', 1954, 1, 10.0),
('Lord of the Rings: The Two Towers', 1954, 0, null),
('Lord of the Rings: The Return of the King', 1955, 1, null);

