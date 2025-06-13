<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi;



enum DatabaseType: string
{

	case Mariadb = 'mariadb';
	case Mysql = 'mysql';
	case Postgres = 'postgres';
	case Sqlite = 'sqlite';



	public function isTableExpressionAllowedAsDataSource(): bool
	{
		return $this !== self::Mysql;
	}



	/**
	 * @return array{
	 *   ansi_quotes: bool,
	 * }
	 */
	public function getSqlParserOptions(): array
	{
		return [
			'ansi_quotes' => $this === self::Postgres,
		];
	}



	public function supportsNativeBoolean(): bool
	{
		return $this === self::Postgres;
	}



	/**
	 * @param array<mixed> $subtree
	 * @param callable(mixed): string $subtreeResolver
	 */
	public function getColumnNameForSubtree(array $subtree, callable $subtreeResolver): ?string
	{
		if ($subtree['expr_type'] === 'bracket_expression' || $subtree['expr_type'] === 'expression') {
			return $subtree['base_expr'];
		}

		if ($subtree['expr_type'] === 'aggregate_function' || $subtree['expr_type'] === 'function') {
			return sprintf(
				'%s(%s)',
				$subtree['base_expr'],
				implode(
					', ',
					array_map(
						static fn ($piece) => $subtreeResolver($piece),
						$subtree['sub_tree'],
					),
				),
			);
		}

		if ($subtree['expr_type'] === 'const') {
			if ($this === self::Sqlite) {
				return '?column?';
			}

			return $subtree['base_expr'];
		}

		return null;
	}



	/**
	 * @param array<mixed> $dataSubtree
	 */
	public function getValuesRecordColumnName(int $columnIndex, array $dataSubtree): string
	{
		if ($this === self::Mariadb || $this === self::Mysql) {
			return $dataSubtree['base_expr'];
		}

		return sprintf('column%s', $columnIndex + 1);
	}

}
