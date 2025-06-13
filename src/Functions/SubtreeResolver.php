<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Functions;

use PHPStan;
use Vojtechdobes\PHPStan\Dibi\Query;
use Vojtechdobes\PHPStan\Dibi\QueryTypeResolver;


final class SubtreeResolver
{

	public function __construct(
		private readonly Query\Context $context,
		private readonly QueryTypeResolver $queryTypeResolver,
	) {}



	/**
	 * @param array<mixed> $subtree
	 */
	public function getSubtreeType(
		array $subtree,
	): PHPStan\Type\Type
	{
		return $this->queryTypeResolver->getSubtreeType($this->context, $subtree);
	}

}
