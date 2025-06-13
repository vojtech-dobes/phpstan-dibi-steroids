<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;


final class SubquerySelectionProvider implements SelectionProvider
{

	public function __construct(
		private readonly PHPStan\Type\Type $rowType,
	) {}



	public function getColumnType(string $columnName): PHPStan\Type\Type
	{
		return $this->rowType->getOffsetValueType(
			new PHPStan\Type\Constant\ConstantStringType($columnName),
		);
	}



	/**
	 * @return list<string>
	 */
	public function listColumns(): array
	{
		return array_map(
			static fn ($column) => (string) $column,
			$this->rowType->getIterableKeyType()->getConstantScalarValues(),
		);
	}

}
