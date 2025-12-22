<?php
declare(strict_types=1);

namespace LPwork\Queue;

use LPwork\Database\DatabaseConnectionManager;
use LPwork\Queue\Contract\JobSerializerInterface;
use LPwork\Queue\Contract\QueueDriverInterface;
use LPwork\Queue\Driver\DatabaseQueueDriver;
use LPwork\Queue\Driver\FilesystemQueueDriver;
use LPwork\Queue\Driver\RedisQueueDriver;
use LPwork\Queue\Exception\QueueConfigurationException;
use LPwork\Redis\RedisConnectionManager;

/**
 * Resolves queue drivers by name.
 */
class QueueManager
{
    /**
     * @var QueueConfiguration
     */
    private QueueConfiguration $config;

    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $redisConnections;

    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $databaseConnections;

    /**
     * @var JobSerializerInterface
     */
    private JobSerializerInterface $serializer;

    /**
     * @var array<string, QueueDriverInterface>
     */
    private array $drivers = [];

    /**
     * @param QueueConfiguration        $config
     * @param RedisConnectionManager    $redisConnections
     * @param DatabaseConnectionManager $databaseConnections
     * @param JobSerializerInterface    $serializer
     */
    public function __construct(
        QueueConfiguration $config,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
        JobSerializerInterface $serializer,
    ) {
        $this->config = $config;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
        $this->serializer = $serializer;
    }

    /**
     * @param string|null $queue
     *
     * @return QueueDriverInterface
     */
    public function queue(?string $queue = null): QueueDriverInterface
    {
        $name = $queue ?? $this->config->defaultQueue();

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $driver = $this->createDriver($name, $this->config->queue($name));
        $this->drivers[$name] = $driver;

        return $driver;
    }

    /**
     * @return string
     */
    public function defaultQueue(): string
    {
        return $this->config->defaultQueue();
    }

    /**
     * @param string                $name
     * @param array<string, mixed>  $config
     *
     * @return QueueDriverInterface
     */
    private function createDriver(string $name, array $config): QueueDriverInterface
    {
        $driver = (string) ($config['driver'] ?? 'redis');

        return match ($driver) {
            'redis' => $this->createRedisDriver($config),
            'database' => $this->createDatabaseDriver($config),
            'filesystem' => $this->createFilesystemDriver($config),
            default => throw new QueueConfigurationException(
                \sprintf('Unsupported queue driver "%s" for queue "%s".', $driver, $name),
            ),
        };
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return QueueDriverInterface
     */
    private function createRedisDriver(array $config): QueueDriverInterface
    {
        $connection = (string) ($config['connection'] ?? 'default');
        $redisConfig = (array) ($config['redis'] ?? []);
        $key = (string) ($redisConfig['key'] ?? 'queues:default');
        $mode = (string) ($redisConfig['mode'] ?? 'list');
        $group = (string) ($redisConfig['group'] ?? 'lpwork');
        $consumer = (string) ($redisConfig['consumer'] ?? 'worker');
        $blockSeconds = (int) ($redisConfig['block_seconds'] ?? 5);

        return new RedisQueueDriver(
            $this->redisConnections,
            $connection,
            $key,
            $mode,
            $group,
            $consumer,
            $blockSeconds,
            $this->serializer,
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return QueueDriverInterface
     */
    private function createDatabaseDriver(array $config): QueueDriverInterface
    {
        $connection = (string) ($config['connection'] ?? 'default');
        $table = (string) ($config['database']['table'] ?? 'queue_jobs');

        return new DatabaseQueueDriver(
            $this->databaseConnections,
            $connection,
            $table,
            $this->serializer,
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return QueueDriverInterface
     */
    private function createFilesystemDriver(array $config): QueueDriverInterface
    {
        $path = (string) ($config['filesystem']['path'] ?? \dirname(__DIR__, 2) . '/storage/queue');

        return new FilesystemQueueDriver($path, $this->serializer);
    }
}
