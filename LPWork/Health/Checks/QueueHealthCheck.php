<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Queue\QueueManager;
use Throwable;

/**
 * Represents the queue health check framework component.
 */
final readonly class QueueHealthCheck implements HealthCheck
{
    /**
     * Creates a new QueueHealthCheck instance.
     */
    public function __construct(
        private QueueManager $queues,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'queue';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $connectionName = $this->queues->defaultConnectionName();
        $driverName = $this->queues->connectionDriverName($connectionName);
        $descriptor = $this->queues->connectionDescriptor($connectionName);
        try {
            $connection = $this->queues->default();
            $connection->assertReady();
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy(
                $this->name(),
                sprintf('Queue connection [%s] using driver [%s] at [%s] failed: %s.', $connectionName, $driverName, $descriptor, $throwable::class),
            );
        }

        return HealthCheckResult::healthy(
            $this->name(),
            sprintf('Queue connection [%s] using driver [%s] is ready at [%s].', $connectionName, $driverName, $descriptor),
        );
    }
}
