<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Extensions;

use Dibi;
use PHPStan;
use PhpParser;


final class DibiConnectionDynamicStaticMethodReturnTypeExtension implements PHPStan\Type\DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Dibi\Connection::class;
	}



	public function isStaticMethodSupported(
		PHPStan\Reflection\MethodReflection $methodReflection,
	): bool
	{
		return in_array($methodReflection->getName(), [
			'expression',
			'literal',
		], true);
	}



	/**
	 * @throws PHPStan\ShouldNotHappenException
	 */
	public function getTypeFromStaticMethodCall(
		PHPStan\Reflection\MethodReflection $methodReflection,
		PhpParser\Node\Expr\StaticCall $methodCall,
		PHPStan\Analyser\Scope $scope,
	): PHPStan\Type\Type
	{
		return match ($methodReflection->getName()) {
			'expression' => $this->processExpressionMethod($methodCall, $scope),
			'literal' => $this->processLiteralMethod($methodReflection, $methodCall, $scope),
			default => throw new PHPStan\ShouldNotHappenException(),
		};
	}



	private function processExpressionMethod(
		PhpParser\Node\Expr\StaticCall $methodCall,
		PHPStan\Analyser\Scope $scope,
	): PHPStan\Type\Type
	{
		$builder = PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty();

		foreach ($methodCall->getArgs() as $i => $arg) {
			$builder->setOffsetValueType(null, $scope->getType($arg->value));
		}

		return new PHPStan\Type\Generic\GenericObjectType(
			Dibi\Expression::class,
			[$builder->getArray()],
		);
	}



	private function processLiteralMethod(
		PHPStan\Reflection\MethodReflection $methodReflection,
		PhpParser\Node\Expr\StaticCall $methodCall,
		PHPStan\Analyser\Scope $scope,
	): PHPStan\Type\Type
	{
		$args = $methodCall->getArgs();

		if ($args === []) {
			return PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		return new PHPStan\Type\Generic\GenericObjectType(
			Dibi\Literal::class,
			[$scope->getType($args[0]->value)],
		);
	}

}
