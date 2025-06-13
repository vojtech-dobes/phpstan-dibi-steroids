<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Extensions;

use Dibi;
use PHPStan;
use PhpParser;
use Vojtechdobes\PHPStan\Dibi\FetchMethods;


final class DibiResultDynamicMethodReturnTypeExtension implements PHPStan\Type\DynamicMethodReturnTypeExtension
{

	private PHPStan\Type\ObjectType $dibiRowObjectType;



	public function __construct()
	{
		$this->dibiRowObjectType = new PHPStan\Type\ObjectType(Dibi\Row::class);
	}



	public function getClass(): string
	{
		return Dibi\Result::class;
	}



	public function isMethodSupported(
		PHPStan\Reflection\MethodReflection $methodReflection,
	): bool
	{
		return in_array($methodReflection->getName(), [
			'count',
			'fetch',
			'fetchAll',
			'fetchAssoc',
			'fetchPairs',
			'fetchSingle',
			'getColumnCount',
			'getRowCount',
			'setRowClass',
			'setRowFactory',
		], true);
	}



	/**
	 * @throws PHPStan\ShouldNotHappenException
	 */
	public function getTypeFromMethodCall(
		PHPStan\Reflection\MethodReflection $methodReflection,
		PhpParser\Node\Expr\MethodCall $methodCall,
		PHPStan\Analyser\Scope $scope,
	): PHPStan\Type\Type
	{
		$methodName = $methodReflection->getName();

		$thisType = $scope->getType($methodCall->var);
		$resultType = $thisType->getTemplateType(Dibi\Result::class, 'TResult');

		return match ($methodName) {
			'fetch' => FetchMethods::resolveFetch(
				$resultType,
				$this->listRowClasses(
					rowClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowClass'),
					rowFactoryClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowFactoryClass'),
				),
				normalize: true,
			),
			'fetchAll' => FetchMethods::resolveFetchAll(
				$resultType,
				$this->listRowClasses(
					rowClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowClass'),
					rowFactoryClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowFactoryClass'),
				),
			),
			'fetchAssoc' => FetchMethods::resolveFetchAssoc(
				$resultType,
				$this->listRowClasses(
					rowClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowClass'),
					rowFactoryClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowFactoryClass'),
				),
				$methodCall->getArgs(),
				$scope,
			),
			'fetchPairs' => FetchMethods::resolveFetchPairs(
				$resultType,
				$methodCall->getArgs(),
				$scope,
			),
			'fetchSingle' => FetchMethods::resolveFetchSingle(
				$resultType,
			),
			'getColumnCount' => $this->getGetColumnCountReturnType(
				$resultType,
			),
			'count', 'getRowCount' => $this->getGetRowCountReturnType(
				$resultType,
			),
			'setRowClass' => $this->getSetRowClassReturnType(
				$methodCall,
				$scope,
				resultType: $resultType,
				rowFactoryClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowFactoryClass'),
			),
			'setRowFactory' => $this->getSetRowFactoryReturnType(
				$methodCall,
				$scope,
				resultType: $resultType,
				rowClassType: $thisType->getTemplateType(Dibi\Result::class, 'TRowClass'),
			),
			default => throw new PHPStan\ShouldNotHappenException(),
		};
	}



	private function getGetColumnCountReturnType(
		PHPStan\Type\Type $resultType,
	): PHPStan\Type\Type
	{
		return PHPStan\Type\TypeCombinator::removeNull($resultType)->getArraySize();
	}



	private function getGetRowCountReturnType(
		PHPStan\Type\Type $resultType,
	): PHPStan\Type\Type
	{
		return PHPStan\Type\IntegerRangeType::fromInterval(
			$resultType->isNull()->no() ? 1 : 0,
			null,
		);
	}



	private function getSetRowClassReturnType(
		PhpParser\Node\Expr\MethodCall $methodCall,
		PHPStan\Analyser\Scope $scope,
		PHPStan\Type\Type $resultType,
		PHPStan\Type\Type $rowFactoryClassType,
	): PHPStan\Type\Type
	{
		$possibleRowClassTypes = [];

		$rowClassType = $scope->getType($methodCall->getArgs()[0]->value);

		$objectRowClassType = $rowClassType->getClassStringObjectType();

		if ($this->dibiRowObjectType->isSuperTypeOf($objectRowClassType)->yes()) {
			$possibleRowClassTypes[] = $objectRowClassType;
		}

		if ($rowClassType->isNull()->no() === false) {
			$possibleRowClassTypes[] = new PHPStan\Type\NullType();
		}

		$rowClassType = PHPStan\Type\TypeCombinator::union(...$possibleRowClassTypes);

		return new PHPStan\Type\Generic\GenericObjectType(
			$this->getClass(),
			[
				$resultType,
				$rowClassType,
				$rowFactoryClassType,
				FetchMethods::resolveFetchAll(
					$resultType,
					$this->listRowClasses(
						rowClassType: $rowClassType,
						rowFactoryClassType: $rowFactoryClassType,
					),
				)->getIterableValueType(),
			],
		);
	}



	private function getSetRowFactoryReturnType(
		PhpParser\Node\Expr\MethodCall $methodCall,
		PHPStan\Analyser\Scope $scope,
		PHPStan\Type\Type $resultType,
		PHPStan\Type\Type $rowClassType,
	): PHPStan\Type\Type
	{
		return PHPStan\Type\TypeCombinator::union(
			...array_map(
				fn ($callableParametersAcceptor) => new PHPStan\Type\Generic\GenericObjectType(
					$this->getClass(),
					[
						$resultType,
						$rowClassType,
						$callableParametersAcceptor->getReturnType(),
						FetchMethods::resolveFetchAll(
							$resultType,
							$this->listRowClasses(
								rowClassType: $rowClassType,
								rowFactoryClassType: $callableParametersAcceptor->getReturnType(),
							),
						)->getIterableValueType(),
					],
				),
				$scope->getType($methodCall->getArgs()[0]->value)->getCallableParametersAcceptors($scope),
			),
		);
	}



	/**
	 * @return list<string>
	 */
	private function listRowClasses(
		PHPStan\Type\Type $rowFactoryClassType,
		PHPStan\Type\Type $rowClassType,
	): array
	{
		if ($rowFactoryClassType->isNull()->yes() === false) {
			return $rowFactoryClassType->getObjectClassNames();
		}

		return $rowClassType->getObjectClassNames();
	}

}
