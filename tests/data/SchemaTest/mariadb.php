<?php declare(strict_types=1);

return [
	[
		'TINYINT',
		'int<-128, 127>|null',
	],
	[
		'SMALLINT',
		'int<-32768, 32767>|null',
	],
	[
		'MEDIUMINT',
		'int<-8388608, 8388607>|null',
	],
	[
		'INT',
		'int<-2147483648, 2147483647>|null',
	],
	[
		'BIGINT',
		'int|null',
	],
	[
		'INT UNSIGNED',
		'int<0, 4294967295>|null',
	],
	[
		'BIGINT UNSIGNED',
		'int<0, max>|null',
	],
	[
		'ENUM ("Alice", "Bob", "Charlie")',
		"'Alice'|'Bob'|'Charlie'|null",
	],
	[
		'ENUM ("Alice", "Bob", "Charlie") NOT NULL',
		"'Alice'|'Bob'|'Charlie'",
	],
	[
		'VARCHAR(20) NOT NULL CHECK (`foo` IN ("Alice", "Bob", "Charlie"))',
		"'Alice'|'Bob'|'Charlie'",
	],
	[
		'TINYINT NOT NULL CHECK (`foo` >= 10 && `foo` < 20)',
		'int<-128, 127>', // better inferring not supported yet
	],
];
