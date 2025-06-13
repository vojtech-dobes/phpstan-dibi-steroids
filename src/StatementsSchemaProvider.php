<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;

use Nette;


final class StatementsSchemaProvider implements SchemaProvider
{

	/**
	 * @param array<string, list<string>|string> $statements
	 */
	public function __construct(
		private readonly array $statements,
	) {}



	public function getDatabaseSchema(Database $database): Schema\Database
	{
		$statements = is_array($this->statements[$database->name])
			? $this->statements[$database->name]
			: [$this->statements[$database->name]];

		$tables = [];
		$views = [];

		foreach ($statements as $statement) {
			$statement = Nette\Utils\Strings::replace($statement, '/#([^\n]+)/', '');

			$parsed = $database->sqlParser->parse($statement);

			if (isset($parsed['CREATE']) && isset($parsed['TABLE'])) {
				$tables[] = new Schema\Table(
					name: Helpers::normalizeName($parsed['TABLE']['no_quotes']['parts'][0]),
					columns: array_values(
						array_map(
							fn ($columnDef) => $this->describeColumnFromParsedStatement($database, $columnDef),
							array_filter(
								$parsed['TABLE']['create-def']['sub_tree'],
								static fn (array $item) => $item['expr_type'] === 'column-def',
							),
						),
					),
				);
			} elseif (isset($parsed['CREATE']) && isset($parsed['VIEW'])) {
				$views[] = new Schema\View(
					name: Helpers::normalizeName($parsed['VIEW'][1]),
					definition: trim($statement),
				);
			}
		}

		return new Schema\Database($tables, $views);
	}



	/**
	 * @param array<mixed> $columnDef
	 */
	public function describeColumnFromParsedStatement(Database $database, array $columnDef): Schema\Column
	{
		if ($columnDef['sub_tree'][1]['sub_tree'] !== []) {
			$columnType = $columnDef['sub_tree'][1]['sub_tree'][0]['base_expr'];
		} else {
			$columnType = $columnDef['sub_tree'][1]['base_expr'];
		}

		$name = Helpers::normalizeName($columnDef['sub_tree'][0]['no_quotes']['parts'][0]);

		$dataType = SchemaTypeResolver::resolveNativeType(
			$database,
			$name,
			strtolower($columnType),
			$columnDef['sub_tree'][1],
		);

		return new Schema\Column(
			name: $name,
			readType: $dataType,
			writeType: $dataType,
			hasDefaultValue: array_key_exists('default', $columnDef['sub_tree'][1]),
			isAutoIncrement: $columnDef['sub_tree'][1]['auto_inc'] === true || $columnDef['sub_tree'][1]['primary'] === true,
			isNullable: $columnDef['sub_tree'][1]['nullable'] === true && $columnDef['sub_tree'][1]['primary'] === false,
		);
	}

}
