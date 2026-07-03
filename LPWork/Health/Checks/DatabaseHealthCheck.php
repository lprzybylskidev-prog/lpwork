<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Database\DatabaseManager;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use Throwable;

/**
 * Represents the database health check framework component.
 */
final readonly class DatabaseHealthCheck implements HealthCheck
{
    /**
     * Creates a new DatabaseHealthCheck instance.
     */
    public function __construct(
        private DatabaseManager $database,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'database';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $connection = $this->database->defaultConnectionName();
        $driver = $this->database->connectionDriverName($connection);
        $endpoint = $this->database->connectionEndpoint($connection);

        try {
            $this->database->default()->select('SELECT 1');
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy(
                $this->name(),
                sprintf(
                    'Database connection [%s] using driver [%s]%s failed: %s.',
                    $connection,
                    $driver,
                    $endpoint === null ? '' : " at [{$endpoint}]",
                    $throwable::class,
                ),
            );
        }

        return HealthCheckResult::healthy(
            $this->name(),
            sprintf(
                'Database connection [%s] using driver [%s]%s responded to SELECT 1.',
                $connection,
                $driver,
                $endpoint === null ? '' : " at [{$endpoint}]",
            ),
        );
    }
}
