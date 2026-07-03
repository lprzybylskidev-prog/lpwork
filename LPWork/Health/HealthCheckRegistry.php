<?php

declare(strict_types=1);

namespace LPWork\Health;

use LPWork\Health\Contracts\HealthCheck;

/**
 * Stores and resolves health check registry registrations.
 */
final class HealthCheckRegistry
{
    /**
     * @var array<string, HealthCheck>
     */
    private array $checks = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(HealthCheck $check): void
    {
        $this->checks[$check->name()] = $check;
    }

    /**
     * @return list<HealthCheck>
     */
    public function all(): array
    {
        return array_values($this->checks);
    }
}
