<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Schema;


final class View
{

	public function __construct(
		public readonly string $name,
		public readonly string $definition,
	) {}

}
