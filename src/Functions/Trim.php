<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\DatabaseType;


final class Trim implements FunctionInterface, FunctionInterfaceName
{

	public function getName(
		DatabaseType $databaseType,
		array $subtree,
	): ?string
	{
		if ($databaseType === DatabaseType::Postgres) {
			return 'btrim';
		}

		return null;
	}



	public function getReturnType(
		DatabaseType $databaseType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
	): ?PHPStan\Type\Type
	{
		$usesModifers = (
			count($subtree['sub_tree']) === 1
			&& $subtree['sub_tree'][0]['expr_type'] === 'expression'
			&& count($subtree['sub_tree'][0]['sub_tree']) > 2
			&& strtolower($subtree['sub_tree'][0]['sub_tree'][count($subtree['sub_tree'][0]['sub_tree']) - 2]['base_expr']) === 'from'
		);

		if ($usesModifers && $databaseType === DatabaseType::Sqlite) {
			return null;
		}

		if ($usesModifers) {
			if (count($subtree['sub_tree'][0]['sub_tree']) === 4) {
				$direction = strtolower($subtree['sub_tree'][0]['sub_tree'][0]);
				$inputArgumentType = $subtreeResolver->getSubtreeType($subtree['sub_tree'][0]['sub_tree'][3]);
				$removeConstantStrings = $subtreeResolver->getSubtreeType($subtree['sub_tree'][0]['sub_tree'][1])->getConstantStrings();
			} elseif (count($subtree['sub_tree'][0]['sub_tree']) === 3) {
				$direction = 'both';
				$inputArgumentType = $subtreeResolver->getSubtreeType($subtree['sub_tree'][0]['sub_tree'][2]);
				$removeConstantStrings = $subtreeResolver->getSubtreeType($subtree['sub_tree'][0]['sub_tree'][0])->getConstantStrings();
			} else {
				return null;
			}

			$inputConstantStrings = PHPStan\Type\TypeCombinator::removeNull($inputArgumentType)->getConstantStrings();
		} else {
			$direction = 'both';
			$inputArgumentType = $subtreeResolver->getSubtreeType($subtree['sub_tree'][0]);
			$removeConstantStrings = isset($subtree['sub_tree'][1])
				? $subtreeResolver->getSubtreeType($subtree['sub_tree'][1])->getConstantStrings()
				: null;

			$inputConstantStrings = PHPStan\Type\TypeCombinator::removeNull($inputArgumentType)->getConstantStrings();
		}

		if (in_array($direction, ['both', 'leading', 'trailing'], true) === false) {
			return null;
		}

		if ($inputConstantStrings === [] || $removeConstantStrings === []) {
			return $inputArgumentType;
		}

		if ($removeConstantStrings === null) {
			$removeConstantStrings = [
				new PHPStan\Type\Constant\ConstantStringType(' '),
			];
		}

		$result = PHPStan\Type\TypeCombinator::union(
			...array_merge(
				...array_map(
					static fn ($inputString) => array_map(
						static fn ($removeString) => new PHPStan\Type\Constant\ConstantStringType(
							match ($direction) {
								'both' => trim($inputString->getValue(), $removeString->getValue()),
								'leading' => ltrim($inputString->getValue(), $removeString->getValue()),
								'trailing' => rtrim($inputString->getValue(), $removeString->getValue()),
							},
						),
						$removeConstantStrings,
					),
					$inputConstantStrings,
				),
			),
		);

		if ($inputArgumentType->isNull()->no() === false) {
			$result = PHPStan\Type\TypeCombinator::addNull($result);
		}

		return $result;
	}

}
