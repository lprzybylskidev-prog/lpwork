<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Config\ArrayConfigReader;
use LPWork\Config\NamedDriverConfig;
use LPWork\Config\NamedDriverConfigFactory;
use LPWork\Events\EventDispatcher;
use LPWork\Queue\Events\QueueJobQueued;
use LPWork\Queue\Exceptions\InvalidQueueConfigException;
use LPWork\Queue\Exceptions\InvalidQueueConnectionException;
use LPWork\Queue\Exceptions\MissingQueueConfigException;
use LPWork\Time\Contracts\Clock;

/**
 * Resolves queue connections, dispatches jobs, and exposes queue runtime settings.
 */
final class QueueManager
{
    /**
     * @var array<string, QueueConnection>
     */
    private array $connections = [];

    private ArrayConfigReader $reader;

    private NamedDriverConfig $connectionConfig;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly QueueDriverFactory $driverFactory,
        private readonly Clock $clock,
        private readonly QueuePayloadSerializer $serializer = new QueuePayloadSerializer(),
        private readonly ?EventDispatcher $events = null,
        private readonly ?QueueDebugCollector $debugCollector = null,
    ) {
        $this->reader = $this->reader($config);
        $this->connectionConfig = $this->connectionConfig($config);
    }

    /**
     * Returns the configured default queue connection.
     */
    public function default(): QueueConnection
    {
        return $this->connection($this->defaultConnectionName());
    }

    /**
     * Returns the configured queue connection name used when dispatch options omit one.
     */
    public function defaultConnectionName(): string
    {
        return $this->connectionConfig->defaultName();
    }

    /**
     * Returns the queue name used when dispatch options omit one.
     */
    public function defaultQueueName(): string
    {
        return $this->reader->string('queue');
    }

    /**
     * Returns a named queue connection, creating and caching it on first use.
     */
    public function connection(string $name): QueueConnection
    {
        if (array_key_exists($name, $this->connections)) {
            return $this->connections[$name];
        }

        $config = $this->connectionConfig->entry($name, static fn(string $name): InvalidQueueConnectionException => new InvalidQueueConnectionException($name));

        $this->connections[$name] = new QueueConnection(
            name: $name,
            driver: $this->driverFactory->create($config, $this->connectionConfig->entryKey($name)),
        );

        return $this->connections[$name];
    }

    /**
     * Serializes and pushes a job to the selected queue connection.
     */
    public function dispatch(object $job, ?QueueDispatchOptions $options = null): string
    {
        $options ??= new QueueDispatchOptions();
        $queue = $options->queue ?? $this->defaultQueueName();
        $connection = $options->connection === null ? $this->default() : $this->connection($options->connection);
        $now = $this->clock->now()->getTimestamp();
        $maxAttempts = $options->maxAttempts ?? $this->retryReader()->int('max_attempts', 'retry.max_attempts');

        $payload = new QueuedJobPayload(
            id: bin2hex(random_bytes(16)),
            queue: $queue,
            jobClass: $job::class,
            body: $this->serializer->serialize($job),
            maxAttempts: $maxAttempts,
            availableAt: $now + $options->delaySeconds,
            createdAt: $now,
        );
        $started = hrtime(true);
        $id = $connection->push($payload);
        $this->debugCollector?->record(
            status: 'queued',
            connection: $connection->name,
            queue: $queue,
            job: $job::class,
            id: $id,
            durationMs: round((hrtime(true) - $started) / 1_000_000, 3),
            context: [
                'Delay seconds' => $options->delaySeconds,
                'Max attempts' => $maxAttempts,
            ],
        );
        $this->events?->dispatch(new QueueJobQueued($connection->name, $queue, $job::class, $id));

        return $id;
    }

    /**
     * Returns the number of seconds before a reserved job is considered expired.
     */
    public function retryAfterSeconds(): int
    {
        return $this->retryReader()->int('retry_after_seconds', 'retry.retry_after_seconds');
    }

    /**
     * Returns the default delay before a failed job is retried.
     */
    public function retryDelaySeconds(): int
    {
        return $this->retryReader()->int('delay_seconds', 'retry.delay_seconds');
    }

    /**
     * Returns how long completed queue records should be retained.
     */
    public function completedRetentionSeconds(): int
    {
        return $this->retentionReader()->int('completed_seconds', 'retention.completed_seconds');
    }

    /**
     * Returns how long failed queue records should be retained.
     */
    public function failedRetentionSeconds(): int
    {
        return $this->retentionReader()->int('failed_seconds', 'retention.failed_seconds');
    }

    /**
     * Returns all configured queue connection names.
     *
     * @return list<string>
     */
    public function connectionNames(): array
    {
        return $this->connectionConfig->names();
    }

    /**
     * Returns the configured driver type for a named queue connection.
     */
    public function connectionDriverName(string $name): string
    {
        $config = $this->connectionConfig->entry($name, static fn(string $name): InvalidQueueConnectionException => new InvalidQueueConnectionException($name));
        $driver = $config['driver'] ?? null;

        return is_string($driver) && $driver !== '' ? $driver : 'unknown';
    }

    /**
     * Returns a non-secret endpoint or storage summary for a named queue connection.
     */
    public function connectionDescriptor(string $name): string
    {
        $config = $this->connectionConfig->entry($name, static fn(string $name): InvalidQueueConnectionException => new InvalidQueueConnectionException($name));
        $driver = $this->connectionDriverName($name);

        if ($driver === 'redis') {
            $host = $config['host'] ?? null;
            $port = $config['port'] ?? null;

            if (is_string($host) && $host !== '' && is_int($port)) {
                return "{$host}:{$port}";
            }
        }

        if ($driver === 'database') {
            $connection = $config['connection'] ?? null;
            $table = $config['table'] ?? null;

            return sprintf(
                'connection [%s] table [%s]',
                is_string($connection) && $connection !== '' ? $connection : 'default',
                is_string($table) && $table !== '' ? $table : 'unknown',
            );
        }

        if ($driver === 'sqs') {
            $queueUrl = $config['queue_url'] ?? null;

            return is_string($queueUrl) && $queueUrl !== '' ? $queueUrl : 'queue URL not configured';
        }

        return $driver;
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

    /**
     * @param array<array-key, mixed> $config
     */
    private function connectionConfig(array $config): NamedDriverConfig
    {
        return new NamedDriverConfigFactory()->create(
            config: $config,
            entriesKey: 'connections',
            missingException: static fn(string $key): MissingQueueConfigException => new MissingQueueConfigException($key),
            invalidException: static fn(string $key): InvalidQueueConfigException => new InvalidQueueConfigException($key),
        );
    }

    private function retryReader(): ArrayConfigReader
    {
        return $this->reader($this->reader->array('retry'));
    }

    private function retentionReader(): ArrayConfigReader
    {
        return $this->reader($this->reader->array('retention'));
    }
}
