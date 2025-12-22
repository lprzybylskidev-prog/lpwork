<?php
declare(strict_types=1);

namespace LPwork\Queue;

use Carbon\CarbonImmutable;

/**
 * Represents a single queued job with payload and metadata.
 */
final class QueueJob
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $queue;

    /**
     * @var array<string, mixed>
     */
    private array $payload;

    /**
     * @var int
     */
    private int $attempts;

    /**
     * @var int|null
     */
    private ?int $maxAttempts;

    /**
     * @var CarbonImmutable|null
     */
    private ?CarbonImmutable $availableAt;

    /**
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * @param string                   $id
     * @param string                   $queue
     * @param array<string, mixed>     $payload
     * @param int                      $attempts
     * @param int|null                 $maxAttempts
     * @param CarbonImmutable|null     $availableAt
     * @param array<string, mixed>     $metadata
     */
    public function __construct(
        string $id,
        string $queue,
        array $payload,
        int $attempts = 0,
        ?int $maxAttempts = null,
        ?CarbonImmutable $availableAt = null,
        array $metadata = [],
    ) {
        $this->id = $id;
        $this->queue = $queue;
        $this->payload = $payload;
        $this->attempts = $attempts;
        $this->maxAttempts = $maxAttempts;
        $this->availableAt = $availableAt;
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function queue(): string
    {
        return $this->queue;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return int
     */
    public function attempts(): int
    {
        return $this->attempts;
    }

    /**
     * @return int|null
     */
    public function maxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    /**
     * @return CarbonImmutable|null
     */
    public function availableAt(): ?CarbonImmutable
    {
        return $this->availableAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return QueueJob
     */
    public function withMetadata(array $metadata): QueueJob
    {
        return new QueueJob(
            $this->id,
            $this->queue,
            $this->payload,
            $this->attempts,
            $this->maxAttempts,
            $this->availableAt,
            $metadata,
        );
    }

    /**
     * @param int $attempts
     *
     * @return QueueJob
     */
    public function withAttempts(int $attempts): QueueJob
    {
        return new QueueJob(
            $this->id,
            $this->queue,
            $this->payload,
            $attempts,
            $this->maxAttempts,
            $this->availableAt,
            $this->metadata,
        );
    }
}
