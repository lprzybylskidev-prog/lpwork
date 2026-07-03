<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Container\Container;
use LPWork\Queue\Exceptions\InvalidQueuedJobException;

/**
 * Represents the queue job runner framework component.
 */
final readonly class QueueJobRunner
{
    /**
     * Creates a new QueueJobRunner instance.
     */
    public function __construct(
        private Container $container,
        private QueuePayloadSerializer $serializer = new QueuePayloadSerializer(),
    ) {}

    /**
     * Runs run.
     */
    public function run(ReservedJob $job): void
    {
        $instance = $this->serializer->restore($job->payload);

        if (!method_exists($instance, 'handle')) {
            throw InvalidQueuedJobException::missingHandler($job->payload->jobClass);
        }

        $this->container->call([$instance, 'handle']);
    }
}
