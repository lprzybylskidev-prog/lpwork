<?php
declare(strict_types=1);

namespace LPwork\Queue\Driver;

use LPwork\Queue\Contract\JobSerializerInterface;
use LPwork\Queue\Contract\QueueDriverInterface;
use LPwork\Queue\Exception\QueueConfigurationException;
use LPwork\Queue\QueueJob;
use LPwork\Redis\RedisConnectionManager;
use Predis\Client;

/**
 * Redis-based queue driver (list or stream).
 */
class RedisQueueDriver implements QueueDriverInterface
{
    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $connections;

    /**
     * @var string
     */
    private string $connectionName;

    /**
     * @var string
     */
    private string $key;

    /**
     * @var string
     */
    private string $mode;

    /**
     * @var string
     */
    private string $group;

    /**
     * @var string
     */
    private string $consumer;

    /**
     * @var int
     */
    private int $blockSeconds;

    /**
     * @var JobSerializerInterface
     */
    private JobSerializerInterface $serializer;

    /**
     * @param RedisConnectionManager $connections
     * @param string                 $connectionName
     * @param string                 $key
     * @param string                 $mode
     * @param string                 $group
     * @param string                 $consumer
     * @param int                    $blockSeconds
     * @param JobSerializerInterface $serializer
     */
    public function __construct(
        RedisConnectionManager $connections,
        string $connectionName,
        string $key,
        string $mode,
        string $group,
        string $consumer,
        int $blockSeconds,
        JobSerializerInterface $serializer,
    ) {
        $this->connections = $connections;
        $this->connectionName = $connectionName;
        $this->key = $key;
        $this->mode = $mode;
        $this->group = $group;
        $this->consumer = $consumer;
        $this->blockSeconds = $blockSeconds;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function push(QueueJob $job): void
    {
        $payload = $this->serializer->serialize($job);
        $redis = $this->redis();

        if ($this->mode === 'stream') {
            $redis->executeRaw(['XADD', $this->key, '*', 'job', $payload]);
            return;
        }

        $redis->executeRaw(['LPUSH', $this->key, $payload]);
    }

    /**
     * @inheritDoc
     */
    public function pop(int $timeoutSeconds): ?QueueJob
    {
        $redis = $this->redis();
        $blockSeconds = $timeoutSeconds > 0 ? $timeoutSeconds : $this->blockSeconds;

        if ($this->mode === 'stream') {
            $this->ensureGroup($redis);

            $response = $redis->executeRaw([
                'XREADGROUP',
                'GROUP',
                $this->group,
                $this->consumer,
                'BLOCK',
                $blockSeconds * 1000,
                'COUNT',
                1,
                'STREAMS',
                $this->key,
                '>',
            ]);

            if (!\is_array($response) || !isset($response[0][1][0])) {
                return null;
            }

            $entry = $response[0][1][0];
            $entryId = (string) ($entry[0] ?? '');
            $fields = $this->decodeFields((array) ($entry[1] ?? []));
            $payload = (string) ($fields['job'] ?? '');
            $job = $this->serializer->deserialize($payload);

            return $job->withMetadata(['stream_id' => $entryId]);
        }

        $result = $redis->executeRaw(['BRPOP', $this->key, $blockSeconds]);

        if (!\is_array($result) || !isset($result[1])) {
            return null;
        }

        return $this->serializer->deserialize((string) $result[1]);
    }

    /**
     * @inheritDoc
     */
    public function ack(QueueJob $job): void
    {
        if ($this->mode !== 'stream') {
            return;
        }

        $streamId = $job->metadata()['stream_id'] ?? null;

        if ($streamId === null) {
            return;
        }

        $redis = $this->redis();
        $redis->executeRaw(['XACK', $this->key, $this->group, $streamId]);
    }

    /**
     * @inheritDoc
     */
    public function reject(QueueJob $job, bool $requeue): void
    {
        if ($this->mode === 'stream') {
            $streamId = $job->metadata()['stream_id'] ?? null;
            $redis = $this->redis();

            if ($streamId !== null) {
                $redis->executeRaw(['XACK', $this->key, $this->group, $streamId]);
            }

            if ($requeue) {
                $this->push($job);
            }

            return;
        }

        if ($requeue) {
            $this->push($job);
        }
    }

    /**
     * @inheritDoc
     */
    public function purge(): void
    {
        $redis = $this->redis();
        $redis->executeRaw(['DEL', $this->key]);
    }

    /**
     * @return Client
     */
    private function redis(): Client
    {
        /** @var Client $client */
        $client = $this->connections->get($this->connectionName)->client();

        return $client;
    }

    /**
     * @param Client $client
     *
     * @return void
     */
    private function ensureGroup(Client $client): void
    {
        if ($this->mode !== 'stream') {
            return;
        }

        try {
            $client->executeRaw(['XGROUP', 'CREATE', $this->key, $this->group, '$', 'MKSTREAM']);
        } catch (\Throwable $e) {
            if (\stripos($e->getMessage(), 'BUSYGROUP') === false) {
                throw new QueueConfigurationException(
                    \sprintf('Failed to ensure Redis stream group: %s', $e->getMessage()),
                    0,
                    $e,
                );
            }
        }
    }

    /**
     * @param array<int|string, string|int> $fields
     *
     * @return array<string, string>
     */
    private function decodeFields(array $fields): array
    {
        // Predis returns flat arrays: [field, value, field, value...]
        $result = [];
        $count = \count($fields);

        for ($i = 0; $i < $count; $i += 2) {
            $key = (string) ($fields[$i] ?? '');
            $value = (string) ($fields[$i + 1] ?? '');
            $result[$key] = $value;
        }

        return $result;
    }
}
