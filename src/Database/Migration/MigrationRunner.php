<?php
declare(strict_types=1);

namespace LPwork\Database\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Database\Migration\Contract\MigrationProviderInterface;
use LPwork\Database\Migration\Exception\MigrationConfigurationException;
use LPwork\Database\Migration\Exception\MigrationErrorException;

/**
 * Runs migrations for a given connection using Doctrine Migrations.
 */
class MigrationRunner
{
    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $connectionManager;

    /**
     * @var MigrationProviderInterface
     */
    private MigrationProviderInterface $frameworkProvider;

    /**
     * @var MigrationProviderInterface
     */
    private MigrationProviderInterface $appProvider;

    /**
     * @param DatabaseConnectionManager   $connectionManager
     * @param MigrationProviderInterface $frameworkProvider
     * @param MigrationProviderInterface $appProvider
     */
    public function __construct(
        DatabaseConnectionManager $connectionManager,
        MigrationProviderInterface $frameworkProvider,
        MigrationProviderInterface $appProvider,
    ) {
        $this->connectionManager = $connectionManager;
        $this->frameworkProvider = $frameworkProvider;
        $this->appProvider = $appProvider;
    }

    /**
     * Runs migrations for the given connection.
     *
     * @param string $connectionName
     *
     * @return void
     */
    public function migrate(string $connectionName): void
    {
        $dependencyFactory = $this->createDependencyFactory($connectionName);

        try {
            $versionResolver = $dependencyFactory->getVersionAliasResolver();
            $latest = $versionResolver->resolveVersionAlias('latest');

            $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($latest);
            $migratorConfig = new MigratorConfiguration();

            $dependencyFactory->getMigrator()->migrate($plan, $migratorConfig);
        } catch (\Throwable $throwable) {
            throw new MigrationErrorException(
                \sprintf('Migration failed for connection "%s".', $connectionName),
                0,
                $throwable,
            );
        }
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

        $config = new ConfigurationArray([
            'migrations_paths' => [
                $this->migrationNamespace($connectionName) => $paths,
            ],
            'all_or_nothing' => true,
            'metadata_storage' => [
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

        return \array_values(\array_merge($frameworkPaths, $appPaths));
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

        return \sprintf('Migrations\\%s', \ucfirst($normalized));
    }
}
