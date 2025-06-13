DROP TABLE IF EXISTS "authors";
CREATE TABLE "authors" (
	"id" SERIAL PRIMARY KEY,
	"name" TEXT NOT NULL
);

DROP TABLE IF EXISTS "books";
CREATE TABLE "books" (
	"id" BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
	"name" TEXT NOT NULL,
	"year" SMALLINT NOT NULL,
	"notes" TEXT,
	"is_available" BOOLEAN NOT NULL,
	"cost" NUMERIC(10,2) NULL
);

DROP TABLE IF EXISTS "books_x_authors";
CREATE TABLE "books_x_authors" (
	"author_id" BIGINT NOT NULL,
	"book_id" BIGINT NOT NULL
);

INSERT INTO "authors" ("name") VALUES
('J. R. R. Tolkien');

INSERT INTO "books" ("name", "year", "is_available", "cost") VALUES
('Lord of the Rings: The Fellowship of the Ring', 1954, true, 10.0),
('Lord of the Rings: The Two Towers', 1954, false, null),
('Lord of the Rings: The Return of the King', 1955, true, null);
