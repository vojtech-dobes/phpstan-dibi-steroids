<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use PHPStan;


final class ReflectorSchemaProvider implements SchemaProvider
{

	/**
	 * @throws PHPStan\ShouldNotHappenException
	 */
	public function getDatabaseSchema(Database $database): Schema\Database
	{
		$databaseReflector = $database
			->connection
			->getDriver()
			->getReflector();

		$tables = [];

		foreach ($databaseReflector->getTables() as $table) {
			if ($table['view']) {
				continue;
			}

			if ($table['name'] !== 'foo') {
				continue;
			}

			$tables[] = new Schema\Table(
				$table['name'],
				array_values(
					array_map(
						static fn ($column) => new Schema\Column(
							name: $column['name'],
							readType: SchemaTypeResolver::resolveNativeTypeFromReflector($database, $column['name'], strtolower($column['nativetype']), $column),
							writeType: SchemaTypeResolver::resolveNativeTypeFromReflector($database, $column['name'], strtolower($column['nativetype']), $column),
							hasDefaultValue: $column['default'] !== null,
							isAutoIncrement: $column['autoincrement'],
							isNullable: $column['nullable'] && (isset($column['vendor']['pk']) ? $column['vendor']['pk'] === 0 : true),
						),
						$databaseReflector->getColumns($table['name']),
					),
				),
			);
		}

		return new Schema\Database($tables, []);
	}

}
