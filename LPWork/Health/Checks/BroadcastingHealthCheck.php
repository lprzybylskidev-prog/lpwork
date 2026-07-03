<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Broadcasting\BroadcastManager;
use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the broadcasting health check framework component.
 */
final readonly class BroadcastingHealthCheck implements HealthCheck
{
    /**
     * Creates a new BroadcastingHealthCheck instance.
     */
    public function __construct(
        private BroadcastManager $broadcasts,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'broadcasting';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $name = $this->broadcasts->defaultBroadcasterName();
        $this->broadcasts->broadcast(new BroadcastMessage(['health'], 'lpwork.health', ['status' => 'ok']));

        return HealthCheckResult::healthy($this->name(), sprintf('Broadcaster [%s] accepted a probe event.', $name));
    }
}
