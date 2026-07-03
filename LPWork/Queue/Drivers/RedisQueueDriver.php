<?php

declare(strict_types=1);

namespace LPWork\Queue\Drivers;

use LPWork\Queue\Contracts\QueueDriver;
use LPWork\Queue\QueuedJobPayload;
use LPWork\Queue\ReservedJob;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Time\Contracts\Clock;

/**
 * Represents the redis queue driver framework component.
 */
final readonly class RedisQueueDriver implements QueueDriver
{
    /**
     * Creates a new RedisQueueDriver instance.
     */
    public function __construct(
        private RedisClient $redis,
        private Clock $clock,
    ) {}

    /**
     * Performs assert ready.
     */
    public function assertReady(): void
    {
        $key = 'queue:health:' . bin2hex(random_bytes(8));

        $this->redis->set($key, 'ok', 30);
        $this->redis->delete($key);
    }

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): string
    {
        $this->redis->hMSet($this->payloadKey($payload->id), [
            'id' => $payload->id,
            'queue' => $payload->queue,
            'job_class' => $payload->jobClass,
            'payload' => $payload->body,
            'max_attempts' => (string) $payload->maxAttempts,
            'available_at' => (string) $payload->availableAt,
            'created_at' => (string) $payload->createdAt,
            'attempts' => '0',
        ]);
        $this->redis->zAdd($this->pendingKey($payload->queue), $payload->availableAt, $payload->id);

        return $payload->id;
    }

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob
    {
        $id = $this->redis->zPopDue($this->pendingKey($queue), $this->now());

        if ($id === null) {
            return null;
        }

        $data = $this->redis->hGetAll($this->payloadKey($id));

        if ($data === []) {
            return null;
        }

        $attempts = ((int) ($data['attempts'] ?? '0')) + 1;
        $data['attempts'] = (string) $attempts;
        $this->redis->hMSet($this->payloadKey($id), $data);
        $this->redis->zAdd($this->reservedKey($queue), $this->now() + $retryAfterSeconds, $id);

        return new ReservedJob(
            payload: new QueuedJobPayload(
                id: $data['id'],
                queue: $data['queue'],
                jobClass: $data['job_class'],
                body: $data['payload'],
                maxAttempts: (int) $data['max_attempts'],
                availableAt: (int) $data['available_at'],
                createdAt: (int) $data['created_at'],
            ),
            attempts: $attempts,
            driverId: $id,
        );
    }

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void
    {
        $this->redis->zRem($this->reservedKey($job->payload->queue), $job->driverId);
        $this->redis->zAdd($this->pendingKey($job->payload->queue), $this->now() + $delaySeconds, $job->driverId);
    }

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void
    {
        $this->redis->zRem($this->reservedKey($job->payload->queue), $job->driverId);
        $this->redis->delete($this->payloadKey($job->driverId));
    }

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void
    {
        $this->complete($job);
    }

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int
    {
        return 0;
    }

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int
    {
        return 0;
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int
    {
        $deleted = $this->redis->clearPattern("queue:payload:*");
        $this->redis->delete($this->pendingKey($queue));
        $this->redis->delete($this->reservedKey($queue));

        return $deleted;
    }

    private function pendingKey(string $queue): string
    {
        return "queue:{$queue}:pending";
    }

    private function reservedKey(string $queue): string
    {
        return "queue:{$queue}:reserved";
    }

    private function payloadKey(string $id): string
    {
        return "queue:payload:{$id}";
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }
}
