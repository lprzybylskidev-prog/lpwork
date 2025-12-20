<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Config\PhpConfigLoader;
use LPwork\Config\PhpConfigRepository;
use LPwork\Environment\Env;
use LPwork\Filesystem\FilesystemManager;
use LPwork\Http\Routing\RouteLoader;
use LPwork\Database\Migration\FrameworkMigrationProvider;
use LPwork\Database\Migration\Contract\MigrationProviderInterface;
use LPwork\Database\Migration\MigrationRunner;
use LPwork\Database\Seeder\Contract\SeederProviderInterface;
use LPwork\Database\Seeder\FrameworkSeederProvider;
use LPwork\Database\Seeder\SeederRunner;
use LPwork\Database\Contract\DatabaseConnectionInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\Contract\RedisConnectionInterface;
use LPwork\Redis\PredisConnection;
use LPwork\Redis\RedisConnectionManager;
use LPwork\Provider\Contract\ProviderInterface;
use LPwork\Version\FrameworkVersion;

/**
 * Registers services shared between HTTP and CLI runtimes.
 */
class CommonProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            Env::class => \DI\factory(static function (): Env {
                /** @var array<string, string> $envVars */
                $envVars = $_ENV;

                return Env::fromArray($envVars);
            }),
            ConfigRepositoryInterface::class => \DI\factory(static function (
                Env $env,
            ): ConfigRepositoryInterface {
                $configDirectory = \dirname(__DIR__, 2) . "/config/configs";
                $loader = new PhpConfigLoader($env);
                $configs = $loader->loadDirectory($configDirectory);

                return new PhpConfigRepository($configs);
            }),
            RedisConnectionManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): RedisConnectionManager {
                $connections = $config->get("redis.connections", []);
                $default = $config->getString(
                    "redis.default_connection",
                    "default",
                );

                return new RedisConnectionManager($connections, $default);
            }),
            RedisConnectionInterface::class => \DI\factory(static function (
                RedisConnectionManager $manager,
                ConfigRepositoryInterface $config,
            ): RedisConnectionInterface {
                $default = $config->getString(
                    "redis.default_connection",
                    "default",
                );

                return $manager->get($default);
            }),
            DatabaseConnectionManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): DatabaseConnectionManager {
                $connections = $config->get("database.connections", []);
                $default = $config->getString(
                    "database.default_connection",
                    "default",
                );

                return new DatabaseConnectionManager($connections, $default);
            }),
            DatabaseConnectionInterface::class => \DI\factory(static function (
                DatabaseConnectionManager $manager,
                ConfigRepositoryInterface $config,
            ): DatabaseConnectionInterface {
                $default = $config->getString(
                    "database.default_connection",
                    "default",
                );

                return $manager->get($default);
            }),
            FilesystemManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): FilesystemManager {
                $disks = $config->get("filesystem.disks", []);
                $default = $config->getString(
                    "filesystem.default_disk",
                    "local",
                );

                return new FilesystemManager($disks, $default);
            }),
            RouteLoader::class => \DI\autowire(RouteLoader::class)->constructor(
                \dirname(__DIR__, 2) . "/config/routes/routes.php",
                \dirname(__DIR__) . "/Http/Routes/routes.php",
            ),
            FrameworkMigrationProvider::class => \DI\autowire(
                FrameworkMigrationProvider::class,
            ),
            MigrationProviderInterface::class => \DI\get(
                \Config\MigrationProvider::class,
            ),
            MigrationRunner::class => \DI\autowire(MigrationRunner::class)
                ->constructorParameter(
                    "frameworkProvider",
                    \DI\get(FrameworkMigrationProvider::class),
                )
                ->constructorParameter(
                    "appProvider",
                    \DI\get(\Config\MigrationProvider::class),
                ),
            FrameworkSeederProvider::class => \DI\autowire(
                FrameworkSeederProvider::class,
            ),
            SeederProviderInterface::class => \DI\get(
                \Config\SeederProvider::class,
            ),
            SeederRunner::class => \DI\autowire(SeederRunner::class)
                ->constructorParameter(
                    "frameworkProvider",
                    \DI\get(FrameworkSeederProvider::class),
                )
                ->constructorParameter(
                    "appProvider",
                    \DI\get(\Config\SeederProvider::class),
                ),
            FrameworkVersion::class => \DI\autowire(FrameworkVersion::class),
        ]);
    }
}
