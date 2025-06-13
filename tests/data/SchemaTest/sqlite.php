<?php declare(strict_types=1);

return [
	// pseudo: boolean
	['BOOL', '0|1|null'],
	['BOOLEAN', '0|1|null'],

	// real
	['REAL', 'float|null'],
	['DOUBLE', 'float|null'],
	['FLOAT', 'float|null'],
	['FLOAT4', 'float|null'],
	['FLOAT8', 'float|null'],

	// integer
	['TINYINT', 'int|null'],
	['SMALLINT', 'int|null'],
	['MEDIUMINT', 'int|null'],
	['INT', 'int|null'],
	['INTEGER', 'int|null'],
	['BIGINT', 'int|null'],
	['INT2', 'int|null'],
	['INT4', 'int|null'],
	['INT8', 'int|null'],
	['INT UNSIGNED', 'int|null'],
	['UNSIGNED BIG INT', 'int|null'],

	// numeric
	['NUMERIC', 'float|int|null'],
	['DECIMAL', 'float|int|null'],
	['MONEY', 'float|int|null'],
	['DATE', 'float|int|null'],
	['DATETIME', 'float|int|null'],
	['STRING', 'float|int|null'],

	// text
	['BLOB', 'string|null'],
	['CHARACTER', 'string|null'],
	['CLOB', 'string|null'],
	['NCHAR', 'string|null'],
	['NVARCHAR', 'string|null'],
	['TEXT', 'string|null'],
	['VARCHAR', 'string|null'],
	['VARYING CHARACTER', 'string|null'],
	['NATIVE CHARACTER', 'string|null'],
	['LONGVARCHAR', 'string|null'],

	// non null
	['INTEGER NOT NULL', 'int'],

	// unknown
	['PINEAPPLE', 'mixed'],

	// not supported by PHPSQLParser
	['INTEGER NOT NULL CHECK (`foo` IN (0, 1))', '0|1'],

	// primary key not nullable
	['INTEGER PRIMARY KEY', 'int'],
];
