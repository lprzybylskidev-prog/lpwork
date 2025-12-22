<?php
declare(strict_types=1);

namespace LPwork\Queue\Serializer;

use Carbon\CarbonImmutable;
use LPwork\Queue\Contract\JobSerializerInterface;
use LPwork\Queue\QueueJob;

/**
 * JSON-based queue job serializer.
 */
class JsonJobSerializer implements JobSerializerInterface
{
    /**
     * @inheritDoc
     */
    public function serialize(QueueJob $job): string
    {
        $data = [
            'id' => $job->id(),
            'queue' => $job->queue(),
            'payload' => $job->payload(),
            'attempts' => $job->attempts(),
            'max_attempts' => $job->maxAttempts(),
            'available_at' => $job->availableAt()?->toIso8601String(),
            'metadata' => $job->metadata(),
        ];

        return \json_encode($data, \JSON_THROW_ON_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $payload): QueueJob
    {
        /** @var array<string, mixed> $data */
        $data = \json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);

        $availableAt = null;

        if (
            isset($data['available_at']) &&
            \is_string($data['available_at']) &&
            $data['available_at'] !== ''
        ) {
            $availableAt = new CarbonImmutable($data['available_at']);
        }

        return new QueueJob(
            (string) ($data['id'] ?? ''),
            (string) ($data['queue'] ?? ''),
            (array) ($data['payload'] ?? []),
            (int) ($data['attempts'] ?? 0),
            isset($data['max_attempts']) ? (int) $data['max_attempts'] : null,
            $availableAt,
            (array) ($data['metadata'] ?? []),
        );
    }
}
