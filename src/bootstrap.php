<?php declare(strict_types=1);

/**
 * @var Nette\DI\Container $container
 */

$config = $container->getByType(Vojtechdobes\PHPStan\Dibi\Config::class);

$schemaClassGenerator = new Vojtechdobes\PHPStan\Dibi\SchemaClassGenerator(
	$config->generatedDir,
);

foreach ($config->listDatabases() as $databaseName) {
	$schemaClassGenerator->generateSchemaClass(
		$config->getDatabase($databaseName),
		Vojtechdobes\PHPStan\Dibi\Helpers::createSchemaClassName($databaseName),
		$container->getByType(Vojtechdobes\PHPStan\Dibi\SchemaProvider::class),
	);
}
