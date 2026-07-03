<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Queue\Exceptions\InvalidQueuedJobException;
use Throwable;

/**
 * Represents the queue payload serializer framework component.
 */
final class QueuePayloadSerializer
{
    /**
     * Performs the serialize operation.
     */
    public function serialize(object $job): string
    {
        try {
            return serialize($job);
        } catch (Throwable $throwable) {
            throw InvalidQueuedJobException::unserializable($job::class, $throwable);
        }
    }

    /**
     * Performs the restore operation.
     */
    public function restore(QueuedJobPayload $payload): object
    {
        $job = unserialize($payload->body, ['allowed_classes' => true]);

        if (!is_object($job) || !$job instanceof $payload->jobClass) {
            throw InvalidQueuedJobException::cannotRestore($payload->jobClass);
        }

        return $job;
    }
}
