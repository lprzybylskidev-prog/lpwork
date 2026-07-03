<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Session\SessionManager;
use Throwable;

/**
 * Represents the session health check framework component.
 */
final readonly class SessionHealthCheck implements HealthCheck
{
    /**
     * Creates a new SessionHealthCheck instance.
     */
    public function __construct(
        private SessionManager $sessions,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'session';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $driverName = $this->sessions->defaultDriverName();
        $driverType = $this->sessions->driverType($driverName);
        try {
            $driver = $this->sessions->default();
            $session = $driver->start();
            $key = 'lpwork_health_' . bin2hex(random_bytes(4));
            $session->put($key, 'ok');
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy(
                $this->name(),
                sprintf('Session driver [%s] using [%s] failed: %s.', $driverName, $driverType, $throwable::class),
            );
        }

        if ($session->get($key) !== 'ok') {
            return HealthCheckResult::unhealthy($this->name(), sprintf('Session driver [%s] using [%s] did not retain the probe value.', $driverName, $driverType));
        }

        $session->forget($key);
        try {
            $driver->save($session);
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy(
                $this->name(),
                sprintf('Session driver [%s] using [%s] could not save the probe session: %s.', $driverName, $driverType, $throwable::class),
            );
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Session driver [%s] using [%s] can start, mutate, and save a session.', $driverName, $driverType));
    }
}
