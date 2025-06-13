<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Concat implements FunctionInterface
{

	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): PHPStan\Type\Type
	{
		$constantStrings = [];
		$types = [];

		foreach ($subtree['sub_tree'] as $argumentSubtree) {
			$argumentType = $subtreeResolver->getSubtreeType($argumentSubtree);

			if ($databaseType === DatabaseType::Postgres || $databaseType === DatabaseType::Sqlite) {
				if ($argumentType->isNull()->yes()) {
					continue;
				}

				$argumentType = PHPStan\Type\TypeCombinator::removeNull($argumentType);
			}

			if ($argumentType->isNull()->yes()) {
				return new PHPStan\Type\NullType();
			}

			if ($argumentType->isNull()->no() === false) {
				$types[] = new PHPStan\Type\NullType();
			}

			$argumentConstantStrings = $argumentType->getConstantStrings();

			if ($argumentConstantStrings === []) {
				$types[] = new PHPStan\Type\StringType();

				continue;
			}

			$constantStrings[] = $argumentConstantStrings;
		}

		if ($constantStrings !== []) {
			$strings = [''];

			foreach ($constantStrings as $argumentConstantStrings) {
				$appendedStrings = [];

				foreach ($argumentConstantStrings as $constantString) {
					foreach ($strings as $string) {
						$appendedStrings[] = $string . $constantString->getValue();
					}
				}

				$strings = $appendedStrings;
			}

			$types[] = PHPStan\Type\TypeCombinator::union(
				...array_map(
					static fn ($string) => new PHPStan\Type\Constant\ConstantStringType($string),
					$strings,
				),
			);
		}

		return PHPStan\Type\TypeCombinator::union(...$types);
	}

}
