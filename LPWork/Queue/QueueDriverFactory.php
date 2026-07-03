<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Config\ArrayConfigReader;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;
use LPWork\Queue\Contracts\QueueDriver;
use LPWork\Queue\Drivers\DatabaseQueueDriver;
use LPWork\Queue\Drivers\RedisQueueDriver;
use LPWork\Queue\Drivers\SqsQueueDriver;
use LPWork\Queue\Drivers\SyncQueueDriver;
use LPWork\Queue\Exceptions\InvalidQueueConfigException;
use LPWork\Queue\Exceptions\InvalidQueueDriverException;
use LPWork\Queue\Exceptions\MissingQueueConfigException;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Shared\Redis\RedisConfigFactory;
use LPWork\Time\Contracts\Clock;

/**
 * Creates queue driver factory instances from framework configuration.
 */
final readonly class QueueDriverFactory
{
    /**
     * Creates a new QueueDriverFactory instance.
     */
    public function __construct(
        private QueueJobRunner $runner,
        private Clock $clock,
        private ?DatabaseManager $database = null,
        private RedisConfigFactory $redis = new RedisConfigFactory(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): QueueDriver
    {
        $reader = $this->reader($config);
        $driver = $reader->string('driver', "{$key}.driver");

        return match ($driver) {
            'sync' => new SyncQueueDriver($this->runner),
            'database' => new DatabaseQueueDriver(new DatabaseQueueRepository(
                connection: $this->database($this->optionalConnection($reader, $key)),
                clock: $this->clock,
                table: $reader->string('table', "{$key}.table"),
            )),
            'redis' => new RedisQueueDriver(
                redis: new RedisClient($this->redis->create($reader, $config, $key), "queue connection [{$key}]"),
                clock: $this->clock,
            ),
            'sqs' => new SqsQueueDriver(
                queueUrl: $reader->string('queue_url', "{$key}.queue_url"),
                region: $reader->string('region', "{$key}.region"),
                accessKey: $reader->string('access_key', "{$key}.access_key"),
                secretKey: $reader->string('secret_key', "{$key}.secret_key"),
            ),
            default => throw new InvalidQueueDriverException($driver),
        };
    }

    private function database(?string $connection): Connection
    {
        if ($this->database === null) {
            throw new MissingQueueConfigException('database');
        }

        return $connection === null ? $this->database->default() : $this->database->connection($connection);
    }

    private function optionalConnection(ArrayConfigReader $reader, string $key): ?string
    {
        $connection = $reader->optionalString('connection', "{$key}.connection", allowEmpty: true);

        return $connection === '' ? null : $connection;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingQueueConfigException => new MissingQueueConfigException($key),
            invalidException: static fn(string $key): InvalidQueueConfigException => new InvalidQueueConfigException($key),
        );
    }
}
