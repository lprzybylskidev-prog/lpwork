<?php
declare(strict_types=1);

namespace LPwork\Database\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Database\Migration\Contract\MigrationProviderInterface;
use LPwork\Database\Migration\Exception\MigrationConfigurationException;
use LPwork\Database\Migration\Exception\MigrationErrorException;

/**
 * Runs migrations for a given connection using Doctrine Migrations.
 */
class MigrationRunner
{
    /**
     * @var DatabaseConnectionManagerInterface
     */
    private DatabaseConnectionManagerInterface $connectionManager;

    /**
     * @var MigrationProviderInterface
     */
    private MigrationProviderInterface $frameworkProvider;

    /**
     * @var MigrationProviderInterface
     */
    private MigrationProviderInterface $appProvider;

    /**
     * @var MigratorConfiguration
     */
    private MigratorConfiguration $migratorConfiguration;

    /**
     * @param DatabaseConnectionManagerInterface $connectionManager
     * @param MigrationProviderInterface         $frameworkProvider
     * @param MigrationProviderInterface         $appProvider
     */
    public function __construct(
        DatabaseConnectionManagerInterface $connectionManager,
        MigrationProviderInterface $frameworkProvider,
        MigrationProviderInterface $appProvider,
    ) {
        $this->connectionManager = $connectionManager;
        $this->frameworkProvider = $frameworkProvider;
        $this->appProvider = $appProvider;
        $this->migratorConfiguration = new MigratorConfiguration();
    }

    /**
     * Runs migrations for the given connection.
     *
     * @param string $connectionName
     *
     * @return array<int, array<string, string>>
     */
    public function migrate(string $connectionName): array
    {
        $dependencyFactory = $this->createDependencyFactory($connectionName);

        try {
            $versionResolver = $dependencyFactory->getVersionAliasResolver();
            $latest = $versionResolver->resolveVersionAlias('latest');

            $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($latest);
            $migrationsToExecute = $this->mapPlanToExecutedMigrations($plan->getItems());
            $dependencyFactory->getMigrator()->migrate($plan, $this->migratorConfiguration);

            return $migrationsToExecute;
        } catch (\Throwable $throwable) {
            throw new MigrationErrorException(
                \sprintf('Migration failed for connection "%s".', $connectionName),
                0,
                $throwable,
            );
        }
    }

    /**
     * Runs migrations for all configured connections.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function migrateAll(): array
    {
        $results = [];

        foreach ($this->connectionManager->getConnectionNames() as $name) {
            $results[$name] = $this->migrate($name);
        }

        return $results;
    }

    /**
     * @param string $connectionName
     *
     * @return DependencyFactory
     */
    private function createDependencyFactory(string $connectionName): DependencyFactory
    {
        $paths = $this->collectMigrationPaths($connectionName);

        if ($paths === []) {
            throw new MigrationConfigurationException(
                \sprintf('No migration paths configured for connection "%s".', $connectionName),
            );
        }

        $migrationsPaths = $this->mapNamespacesToPaths($paths, $connectionName);

        $config = new ConfigurationArray([
            'migrations_paths' => $migrationsPaths,
            'all_or_nothing' => true,
            'table_storage' => [
                'table_name' => 'migrations',
            ],
        ]);

        /** @var Connection $connection */
        $connection = $this->connectionManager->get($connectionName)->connection();

        return DependencyFactory::fromConnection(
            $config,
            new \Doctrine\Migrations\Configuration\Connection\ExistingConnection($connection),
        );
    }

    /**
     * @param string $connectionName
     *
     * @return array<int, string>
     */
    private function collectMigrationPaths(string $connectionName): array
    {
        $frameworkPaths = $this->frameworkProvider->getMigrationPaths()[$connectionName] ?? [];
        $appPaths = $this->appProvider->getMigrationPaths()[$connectionName] ?? [];

        $paths = \array_merge($frameworkPaths, $appPaths);

        return \array_values(
            \array_filter($paths, static function (string $path): bool {
                return \is_dir($path);
            }),
        );
    }

    /**
     * @param string $connectionName
     *
     * @return string
     */
    private function migrationNamespace(string $connectionName): string
    {
        $normalized = \preg_replace('/[^A-Za-z0-9_]/', '_', $connectionName);
        $normalized = (string) $normalized;

        return \sprintf('Migrations\\%sConnection', \ucfirst($normalized));
    }

    /**
     * Builds migration namespace to directory map.
     *
     * @param array<int, string> $paths
     * @param string             $connectionName
     *
     * @return array<string, string>
     */
    private function mapNamespacesToPaths(array $paths, string $connectionName): array
    {
        $baseNamespace = $this->migrationNamespace($connectionName);
        $mappedPaths = [];

        foreach (\array_values($paths) as $index => $path) {
            $namespace =
                $index === 0 ? $baseNamespace : \sprintf('%s\\App%d', $baseNamespace, $index);

            $mappedPaths[$namespace] = $path;
        }

        return $mappedPaths;
    }

    /**
     * Drops all tables and reruns migrations.
     *
     * @param string $connectionName
     *
     * @return array<int, array<string, string>>
     */
    public function fresh(string $connectionName): array
    {
        $connection = $this->connectionManager->get($connectionName)->connection();
        $schemaManager = $connection->createSchemaManager();
        $platform = $connection->getDatabasePlatform();

        $this->disableForeignKeys($connection, $platform);

        foreach ($schemaManager->listTables() as $table) {
            $schemaManager->dropTable($table->getName());
        }

        $this->enableForeignKeys($connection, $platform);

        return $this->migrate($connectionName);
    }

    /**
     * Drops all tables and reruns migrations for all configured connections.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function freshAll(): array
    {
        $results = [];

        foreach ($this->connectionManager->getConnectionNames() as $name) {
            $results[$name] = $this->fresh($name);
        }

        return $results;
    }

    /**
     * Returns configured connection names.
     *
     * @return array<int, string>
     */
    public function getConnectionNames(): array
    {
        return $this->connectionManager->getConnectionNames();
    }

    /**
     * Returns the default connection name.
     *
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        return $this->connectionManager->getDefaultConnectionName();
    }

    /**
     * @param Connection $connection
     * @param object     $platform
     *
     * @return void
     */
    private function disableForeignKeys(Connection $connection, object $platform): void
    {
        if ($platform instanceof MySQLPlatform) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

            return;
        }

        if ($platform instanceof SQLitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys = OFF');
        }
    }

    /**
     * @param Connection $connection
     * @param object     $platform
     *
     * @return void
     */
    private function enableForeignKeys(Connection $connection, object $platform): void
    {
        if ($platform instanceof MySQLPlatform) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            return;
        }

        if ($platform instanceof SQLitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Maps Doctrine migration plan items to executed migration metadata.
     *
     * @param array<int, \Doctrine\Migrations\Metadata\MigrationPlan> $items
     *
     * @return array<int, array<string, string>>
     */
    private function mapPlanToExecutedMigrations(array $items): array
    {
        $executed = [];

        foreach ($items as $item) {
            $executed[] = [
                'version' => (string) $item->getVersion(),
                'description' => $item->getMigration()->getDescription(),
                'class' => \get_class($item->getMigration()),
            ];
        }

        return $executed;
    }
}
