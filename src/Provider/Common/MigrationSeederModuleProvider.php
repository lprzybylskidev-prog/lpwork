<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Database\Migration\Contract\MigrationProviderInterface;
use LPwork\Database\Migration\FrameworkMigrationProvider;
use LPwork\Database\Migration\MigrationRunner;
use LPwork\Database\Seeder\Contract\SeederProviderInterface;
use LPwork\Database\Seeder\FrameworkSeederProvider;
use LPwork\Database\Seeder\SeederRunner;

/**
 * Registers migration and seeder providers.
 */
final class MigrationSeederModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FrameworkMigrationProvider::class => \DI\autowire(FrameworkMigrationProvider::class),
            MigrationProviderInterface::class => \DI\get(\Config\MigrationProvider::class),
            MigrationRunner::class => \DI\autowire(MigrationRunner::class)
                ->constructorParameter(
                    'frameworkProvider',
                    \DI\get(FrameworkMigrationProvider::class),
                )
                ->constructorParameter('appProvider', \DI\get(\Config\MigrationProvider::class)),
            FrameworkSeederProvider::class => \DI\autowire(FrameworkSeederProvider::class),
            SeederProviderInterface::class => \DI\get(\Config\SeederProvider::class),
            SeederRunner::class => \DI\autowire(SeederRunner::class)
                ->constructorParameter('frameworkProvider', \DI\get(FrameworkSeederProvider::class))
                ->constructorParameter('appProvider', \DI\get(\Config\SeederProvider::class)),
        ]);
    }
}
