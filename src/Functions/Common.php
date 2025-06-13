<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;


final class Common
{

	/**
	 * @param array<mixed> $subtree
	 */
	public static function resolveGivenTypeOrNullWithNullableInput(
		PHPStan\Type\Type $resultType,
		SubtreeResolver $subtreeResolver,
		array $subtree,
		int $inputArgumentIndex = 0,
	): PHPStan\Type\Type
	{
		if (isset($subtree['sub_tree'][$inputArgumentIndex])) {
			$isInputNull = $subtreeResolver->getSubtreeType($subtree['sub_tree'][$inputArgumentIndex])->isNull();
		} else {
			$isInputNull = PHPStan\TrinaryLogic::createNo();
		}

		if ($isInputNull->no() === false) {
			$resultType = PHPStan\Type\TypeCombinator::addNull($resultType);
		}

		return $resultType;
	}



	/**
	 * @param array<mixed> $subtree
	 */
	public static function resolveStringOrNullWithNullableInput(
		SubtreeResolver $subtreeResolver,
		array $subtree,
		int $inputArgumentIndex = 0,
	): PHPStan\Type\Type
	{
		return self::resolveGivenTypeOrNullWithNullableInput(
			new PHPStan\Type\StringType(),
			$subtreeResolver,
			$subtree,
			$inputArgumentIndex,
		);
	}

}
