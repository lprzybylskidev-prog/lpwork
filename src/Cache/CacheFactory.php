<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Cache\Exception\CacheConfigurationException;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Builds cache pools (PSR-6) and wraps them for PSR-16 where needed.
 */
class CacheFactory
{
    /**
     * @param CacheConfiguration         $configuration
     * @param RedisConnectionManager     $redisConnections
     * @param DatabaseConnectionManager  $databaseConnections
     *
     * @return CacheItemPoolInterface
     */
    public function createDefaultPool(
        CacheConfiguration $configuration,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
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
     * @param string                     $name
     * @param CacheConfiguration         $configuration
     * @param RedisConnectionManager     $redisConnections
     * @param DatabaseConnectionManager  $databaseConnections
     *
     * @return CacheItemPoolInterface
     */
    public function createPool(
        string $name,
        CacheConfiguration $configuration,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
    ): CacheItemPoolInterface {
        $poolConfig = $configuration->pool($name);
        $driver = (string) ($poolConfig['driver'] ?? 'array');
        $namespace = (string) ($poolConfig['namespace'] ?? '');
        $defaultTtl = $poolConfig['default_ttl'] ?? null;

        if ($defaultTtl !== null) {
            $defaultTtl = (int) $defaultTtl;
        }

        return match ($driver) {
            'array' => new ArrayAdapter(storeSerialized: false, defaultLifetime: $defaultTtl),
            'filesystem' => new FilesystemAdapter(
                namespace: $namespace,
                defaultLifetime: $defaultTtl,
                directory: (string) ($poolConfig['path'] ?? ''),
            ),
            'redis' => $this->createRedisAdapter(
                $poolConfig,
                $redisConnections,
                $namespace,
                $defaultTtl,
            ),
            'pdo' => $this->createPdoAdapter(
                $poolConfig,
                $databaseConnections,
                $namespace,
                $defaultTtl,
            ),
            default => throw new CacheConfigurationException(
                \sprintf('Cache driver "%s" is not supported.', $driver),
            ),
        };
    }

    /**
     * @param array<string, mixed>       $config
     * @param RedisConnectionManager     $connections
     * @param string                     $namespace
     * @param int|null                   $defaultTtl
     *
     * @return CacheItemPoolInterface
     */
    private function createRedisAdapter(
        array $config,
        RedisConnectionManager $connections,
        string $namespace,
        ?int $defaultTtl,
    ): CacheItemPoolInterface {
        $connectionName = (string) ($config['connection'] ?? 'default');
        $client = $connections->get($connectionName)->client();

        return new RedisAdapter($client, $namespace, $defaultTtl);
    }

    /**
     * @param array<string, mixed>       $config
     * @param DatabaseConnectionManager  $connections
     * @param string                     $namespace
     * @param int|null                   $defaultTtl
     *
     * @return CacheItemPoolInterface
     */
    private function createPdoAdapter(
        array $config,
        DatabaseConnectionManager $connections,
        string $namespace,
        ?int $defaultTtl,
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
