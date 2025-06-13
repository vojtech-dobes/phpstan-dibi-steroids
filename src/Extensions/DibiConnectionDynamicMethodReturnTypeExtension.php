<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Extensions;

use Dibi;
use PHPStan;
use PhpParser;
use Vojtechdobes\PHPStan\Dibi\Config;
use Vojtechdobes\PHPStan\Dibi\FetchMethods;
use Vojtechdobes\PHPStan\Dibi\Helpers;
use Vojtechdobes\PHPStan\Dibi\SchemaClassOraculum;


final class DibiConnectionDynamicMethodReturnTypeExtension implements PHPStan\Type\DynamicMethodReturnTypeExtension
{

	public function __construct(
		private readonly Config $config,
		private readonly PHPStan\Reflection\ReflectionProvider $reflectionProvider,
	) {}



	public function getClass(): string
	{
		return Dibi\Connection::class;
	}



	public function isMethodSupported(
		PHPStan\Reflection\MethodReflection $methodReflection,
	): bool
	{
		return in_array($methodReflection->getName(), [
			'fetch',
			'fetchAll',
			'fetchPairs',
			'fetchSingle',
			'nativeQuery',
			'query',
			'translate',
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
		$database = $this->config->getDatabase(
			$this->getDatabaseName(
				$scope->getType($methodCall->var),
			),
		);

		$methodName = $methodReflection->getName();
		$methodArgs = $methodCall->getArgs();

		if ($methodName === 'nativeQuery') {
			$sqlStrings = array_map(
				static fn ($constantStringType) => $constantStringType->getValue(),
				$scope->getType($methodArgs[0]->value)->getConstantStrings(),
			);
		} else {
			[$sqlStrings] = $database->translateSimulator->buildSqlStrings(
				array_map(
					static fn ($argument) => $scope->getType($argument->value),
					$methodArgs,
				),
				array_map(
					static fn ($argument) => $argument->unpack,
					$methodArgs,
				),
			);
		}

		if ($sqlStrings === []) {
			return PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		if ($methodName === 'translate') {
			return PHPStan\Type\TypeCombinator::union(
				...array_map(
					static fn ($sqlString) => new PHPStan\Type\Constant\ConstantStringType($sqlString),
					$sqlStrings,
				),
			);
		}

		$schemaClassOraculum = $this->createSchemaClassOraculum($database->name);

		if ($schemaClassOraculum === null) {
			return PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		$queryTypeResolver = $database->createQueryTypeResolver($schemaClassOraculum);

		$rowTypes = [];

		foreach ($sqlStrings as $sqlString) {
			$rowType = $queryTypeResolver->resolveQueryType($sqlString);

			if ($rowType === null) {
				continue;
			}

			$rowTypes[] = $rowType;
		}

		if ($rowTypes === []) {
			return PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		$rowType = PHPStan\Type\TypeCombinator::union(...$rowTypes);

		return match ($methodName) {
			'fetch' => FetchMethods::resolveFetch(
				$rowType,
				[Dibi\Row::class],
				normalize: true,
			),
			'fetchAll' => FetchMethods::resolveFetchAll(
				$rowType,
				[Dibi\Row::class],
			),
			'fetchPairs' => FetchMethods::resolveFetchPairs(
				$rowType,
				[],
				$scope,
			),
			'fetchSingle' => FetchMethods::resolveFetchSingle(
				$rowType,
			),
			'nativeQuery',
			'query' => new PHPStan\Type\Generic\GenericObjectType(
				Dibi\Result::class,
				[
					$rowType,
					new PHPStan\Type\ObjectType(Dibi\Row::class),
					new PHPStan\Type\NullType(),
					FetchMethods::resolveFetchAll(
						$rowType,
						[Dibi\Row::class],
					)->getIterableValueType(),
				],
			),
			default => throw new PHPStan\ShouldNotHappenException(),
		};
	}



	private function createSchemaClassOraculum(string $databaseName): ?SchemaClassOraculum
	{
		$className = Helpers::createSchemaClassName($databaseName);

		// following check bypasses PHPStan\Testing\RuleTestCase
		// not being able to discover file generated on-the-fly
		if (
			@class_exists($className) === false
			&& is_file($classNameFile = ($this->config->generatedDir . '/' . $className . '.php'))
		) {
			require_once $classNameFile;
		}

		if ($this->reflectionProvider->hasClass($className) === false) {
			return null;
		}

		return new SchemaClassOraculum(
			$this->reflectionProvider->getClass($className),
		);
	}



	private function getDatabaseName(PHPStan\Type\Type $connectionType): string
	{
		$databaseNameType = $connectionType->getTemplateType(Dibi\Connection::class, 'TDatabase');

		if ($databaseNameType->isNull()->yes()) {
			return 'main';
		}

		return array_map(
			static fn ($type) => $type->getValue(),
			$databaseNameType->getConstantStrings(),
		)[0] ?? 'main';
	}

}
