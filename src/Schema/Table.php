<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Schema;


final class Table
{

	/**
	 * @param list<Column> $columns
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $columns,
	) {}

}
