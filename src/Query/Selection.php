<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;

use PHPStan;


final class Selection
{

	public function __construct(
		public readonly bool $isAggregation,
		public readonly PHPStan\Type\Type $type,
	) {}

}
