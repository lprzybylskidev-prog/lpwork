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
use LPwork\Http\Middleware\SessionMiddleware;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionConfigurationException;
use LPwork\Http\Session\RandomSessionIdGenerator;
use LPwork\Http\Session\SessionConfiguration;
use LPwork\Http\Session\SessionManager;
use LPwork\Http\Session\Store\DatabaseSessionStore;
use LPwork\Http\Session\Store\FilesystemSessionStore;
use LPwork\Http\Session\Store\PhpSessionStore;
use LPwork\Http\Session\Store\RedisSessionStore;
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
            SessionConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): SessionConfiguration {
                $sessionConfig = $config->get("session", []);

                return new SessionConfiguration((array) $sessionConfig);
            }),
            SessionIdGeneratorInterface::class => \DI\autowire(
                RandomSessionIdGenerator::class,
            ),
            SessionStoreInterface::class => \DI\factory(static function (
                SessionConfiguration $config,
                SessionIdGeneratorInterface $idGenerator,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManager $databaseConnections,
                FilesystemManager $filesystemManager,
            ): SessionStoreInterface {
                $driver = $config->driver();

                if ($driver === "php") {
                    $phpConfig = $config->driverConfig("php");
                    $name = (string) ($phpConfig["name"] ?? "LPWORKSESSID");

                    return new PhpSessionStore($name);
                }

                if ($driver === "redis") {
                    $redisConfig = $config->driverConfig("redis");
                    $connection =
                        (string) ($redisConfig["connection"] ?? "default");
                    $prefix = (string) ($redisConfig["prefix"] ?? "session:");

                    return new RedisSessionStore(
                        $redisConnections,
                        $connection,
                        $prefix,
                        $idGenerator,
                    );
                }

                if ($driver === "database") {
                    $dbConfig = $config->driverConfig("database");
                    $connection =
                        (string) ($dbConfig["connection"] ?? "default");
                    $table = (string) ($dbConfig["table"] ?? "sessions");

                    return new DatabaseSessionStore(
                        $databaseConnections,
                        $connection,
                        $table,
                        $idGenerator,
                    );
                }

                if ($driver === "filesystem") {
                    $fsConfig = $config->driverConfig("filesystem");
                    $disk = (string) ($fsConfig["disk"] ?? "local");
                    $path = (string) ($fsConfig["path"] ?? "sessions");

                    return new FilesystemSessionStore(
                        $filesystemManager,
                        $disk,
                        $path,
                        $idGenerator,
                    );
                }

                throw new SessionConfigurationException(
                    \sprintf('Session driver "%s" is not supported.', $driver),
                );
            }),
            SessionManager::class => \DI\autowire(SessionManager::class),
            SessionInterface::class => \DI\factory(static function (
                SessionManager $manager,
            ): SessionInterface {
                return $manager->current();
            }),
            SessionMiddleware::class => \DI\autowire(SessionMiddleware::class),
            FrameworkVersion::class => \DI\autowire(FrameworkVersion::class),
        ]);
    }
}
