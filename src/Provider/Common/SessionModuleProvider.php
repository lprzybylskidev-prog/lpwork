<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Filesystem\Contract\FilesystemManagerInterface;
use LPwork\Http\Middleware\SessionMiddleware;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionConfigurationException;
use LPwork\Http\Session\RandomSessionIdGenerator;
use LPwork\Http\Session\SessionConfiguration;
use LPwork\Http\Session\SessionManager;
use LPwork\Http\Session\Contract\SessionManagerInterface;
use LPwork\Http\Session\Store\DatabaseSessionStore;
use LPwork\Http\Session\Store\FilesystemSessionStore;
use LPwork\Http\Session\Store\PhpSessionStore;
use LPwork\Http\Session\Store\RedisSessionStore;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Registers session handling services.
 */
final class SessionModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SessionConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): SessionConfiguration {
                $sessionConfig = $config->get('session', []);

                return new SessionConfiguration((array) $sessionConfig);
            }),
            SessionIdGeneratorInterface::class => \DI\autowire(RandomSessionIdGenerator::class),
            SessionStoreInterface::class => \DI\factory(static function (
                SessionConfiguration $config,
                SessionIdGeneratorInterface $idGenerator,
                RedisConnectionManagerInterface $redisConnections,
                DatabaseConnectionManagerInterface $databaseConnections,
                FilesystemManagerInterface $filesystemManager,
                ClockInterface $clock,
            ): SessionStoreInterface {
                $driver = $config->driver();

                if ($driver === 'php') {
                    $phpConfig = $config->driverConfig('php');
                    $name = (string) ($phpConfig['name'] ?? 'LPWORKSESSID');

                    return new PhpSessionStore($name, $clock);
                }

                if ($driver === 'redis') {
                    $redisConfig = $config->driverConfig('redis');
                    $connection = (string) ($redisConfig['connection'] ?? 'default');
                    $prefix = (string) ($redisConfig['prefix'] ?? 'session:');

                    return new RedisSessionStore(
                        $redisConnections,
                        $connection,
                        $prefix,
                        $idGenerator,
                        $clock,
                    );
                }

                if ($driver === 'database') {
                    $dbConfig = $config->driverConfig('database');
                    $connection = (string) ($dbConfig['connection'] ?? 'default');
                    $table = (string) ($dbConfig['table'] ?? 'sessions');

                    return new DatabaseSessionStore(
                        $databaseConnections,
                        $connection,
                        $table,
                        $idGenerator,
                        $clock,
                    );
                }

                if ($driver === 'filesystem') {
                    $fsConfig = $config->driverConfig('filesystem');
                    $disk = (string) ($fsConfig['disk'] ?? 'local');
                    $path = (string) ($fsConfig['path'] ?? 'sessions');

                    return new FilesystemSessionStore(
                        $filesystemManager,
                        $disk,
                        $path,
                        $idGenerator,
                        $clock,
                    );
                }

                throw new SessionConfigurationException(
                    \sprintf('Session driver "%s" is not supported.', $driver),
                );
            }),
            SessionManager::class => \DI\autowire(SessionManager::class),
            SessionManagerInterface::class => \DI\get(SessionManager::class),
            SessionInterface::class => \DI\factory(static function (
                SessionManager $manager,
            ): SessionInterface {
                return $manager->current();
            }),
            SessionMiddleware::class => \DI\autowire(SessionMiddleware::class),
        ]);
    }
}
