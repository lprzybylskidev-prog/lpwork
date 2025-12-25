<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Cache\Contract\CacheFactoryInterface;
use LPwork\Cache\Exception\CacheConfigurationException;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Builds cache pools (PSR-6) and wraps them for PSR-16 where needed.
 */
class CacheFactory implements CacheFactoryInterface
{
    /**
     * @param CacheConfiguration                   $configuration
     * @param RedisConnectionManagerInterface      $redisConnections
     * @param DatabaseConnectionManagerInterface   $databaseConnections
     *
     * @return CacheItemPoolInterface
     */
    public function createDefaultPool(
        CacheConfiguration $configuration,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
    ): CacheItemPoolInterface {
        $defaultPool = $configuration->defaultPool();

        return $this->createPool(
            $defaultPool,
            $configuration,
            $redisConnections,
            $databaseConnections,
        );
    }

    /**
     * @param string                               $name
     * @param CacheConfiguration                   $configuration
     * @param RedisConnectionManagerInterface      $redisConnections
     * @param DatabaseConnectionManagerInterface   $databaseConnections
     *
     * @return CacheItemPoolInterface
     */
    public function createPool(
        string $name,
        CacheConfiguration $configuration,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
    ): CacheItemPoolInterface {
        $poolConfig = $configuration->pool($name);
        $driver = (string) ($poolConfig['driver'] ?? 'array');
        $namespace = (string) ($poolConfig['namespace'] ?? '');
        $defaultTtl = $poolConfig['default_ttl'] ?? null;
        $defaultLifetime = $defaultTtl === null ? 0 : (int) $defaultTtl;

        return match ($driver) {
            'array' => new ArrayAdapter(storeSerialized: false, defaultLifetime: $defaultLifetime),
            'filesystem' => new FilesystemAdapter(
                namespace: $namespace,
                defaultLifetime: $defaultLifetime,
                directory: (string) ($poolConfig['path'] ?? ''),
            ),
            'redis' => $this->createRedisAdapter(
                $poolConfig,
                $redisConnections,
                $namespace,
                $defaultLifetime,
            ),
            'pdo' => $this->createPdoAdapter(
                $poolConfig,
                $databaseConnections,
                $namespace,
                $defaultLifetime,
            ),
            default => throw new CacheConfigurationException(
                \sprintf('Cache driver "%s" is not supported.', $driver),
            ),
        };
    }

    /**
     * @param array<string, mixed>       $config
     * @param RedisConnectionManagerInterface      $connections
     * @param string                     $namespace
     * @param int                   $defaultTtl
     *
     * @return CacheItemPoolInterface
     */
    private function createRedisAdapter(
        array $config,
        RedisConnectionManagerInterface $connections,
        string $namespace,
        int $defaultTtl,
    ): CacheItemPoolInterface {
        $connectionName = (string) ($config['connection'] ?? 'default');
        $client = $connections->get($connectionName)->client();

        return new RedisAdapter($client, $namespace, $defaultTtl);
    }

    /**
     * @param array<string, mixed>                 $config
     * @param DatabaseConnectionManagerInterface  $connections
     * @param string                               $namespace
     * @param int                             $defaultTtl
     *
     * @return CacheItemPoolInterface
     */
    private function createPdoAdapter(
        array $config,
        DatabaseConnectionManagerInterface $connections,
        string $namespace,
        int $defaultTtl,
    ): CacheItemPoolInterface {
        $connectionName = (string) ($config['connection'] ?? 'default');
        $table = (string) ($config['table'] ?? 'cache_items');
        $dbal = $connections->get($connectionName)->connection();
        $pdo = $dbal->getNativeConnection();

        if (!$pdo instanceof \PDO) {
            throw new CacheConfigurationException(
                \sprintf('Cache pool "%s" requires PDO connection.', $connectionName),
            );
        }

        return new PdoAdapter($pdo, $namespace, $defaultTtl, [
            'db_table' => $table,
        ]);
    }
}
