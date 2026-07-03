<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Container\Container;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Notifications\NotificationChannelRegistry;
use Throwable;

/**
 * Represents the notifications health check framework component.
 */
final readonly class NotificationsHealthCheck implements HealthCheck
{
    /**
     * Creates a new NotificationsHealthCheck instance.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'notifications';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        try {
            $channels = $this->container->make(NotificationChannelRegistry::class);
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy($this->name(), 'Notification channels could not be resolved: ' . $throwable::class . '.');
        }

        if (!$channels instanceof NotificationChannelRegistry) {
            return HealthCheckResult::unhealthy($this->name(), 'Notification channel registry resolved to an invalid object.');
        }

        $names = $channels->names();

        foreach (['mail', 'database', 'broadcast'] as $required) {
            $channels->get($required);
        }

        return HealthCheckResult::healthy($this->name(), 'Notification channels are registered: ' . implode(', ', $names) . '.');
    }
}
