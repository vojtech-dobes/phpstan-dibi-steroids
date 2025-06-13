<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use Dibi;
use PHPSQLParser;


final class Database
{

	public function __construct(
		public readonly Dibi\Connection $connection,
		public readonly string $name,
		public readonly PHPSQLParser\PHPSQLParser $sqlParser,
		public readonly TranslateSimulator $translateSimulator,
		public readonly DatabaseType $type,
	) {}



	public function createQueryTypeResolver(
		?SchemaClassOraculum $schemaClassOraculum,
	): QueryTypeResolver
	{
		return new QueryTypeResolver(
			$this->type,
			new Query\TypeProvider($schemaClassOraculum, []),
			$this->sqlParser,
		);
	}

}
