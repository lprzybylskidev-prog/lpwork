<?php
declare(strict_types=1);

namespace LPwork\Database\Seeder;

use LPwork\Database\DatabaseConnectionManager;
use LPwork\Database\Seeder\Contract\SeederProviderInterface;

/**
 * Runs seeders for a given connection.
 */
class SeederRunner
{
    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $connectionManager;

    /**
     * @var SeederProviderInterface
     */
    private SeederProviderInterface $frameworkProvider;

    /**
     * @var SeederProviderInterface
     */
    private SeederProviderInterface $appProvider;

    /**
     * @param DatabaseConnectionManager $connectionManager
     * @param SeederProviderInterface   $frameworkProvider
     * @param SeederProviderInterface   $appProvider
     */
    public function __construct(
        DatabaseConnectionManager $connectionManager,
        SeederProviderInterface $frameworkProvider,
        SeederProviderInterface $appProvider,
    ) {
        $this->connectionManager = $connectionManager;
        $this->frameworkProvider = $frameworkProvider;
        $this->appProvider = $appProvider;
    }

    /**
     * Executes seeders for the given connection.
     *
     * @param string $connectionName
     *
     * @return int Number of executed seeders.
     */
    public function seed(string $connectionName): int
    {
        $seeders = $this->collectSeeders($connectionName);

        if ($seeders === []) {
            return 0;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connectionManager->get($connectionName)->connection();

        foreach ($seeders as $seeder) {
            $seeder->run();
        }

        return \count($seeders);
    }

    /**
     * @param string $connectionName
     *
     * @return array<int, \LPwork\Database\Seeder\Contract\SeederInterface>
     */
    private function collectSeeders(string $connectionName): array
    {
        $frameworkSeeders = $this->frameworkProvider->getSeeders()[$connectionName] ?? [];
        $appSeeders = $this->appProvider->getSeeders()[$connectionName] ?? [];

        return \array_values(\array_merge($frameworkSeeders, $appSeeders));
    }
}
