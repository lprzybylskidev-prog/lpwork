<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\Throttle\Exceptions\ThrottlePolicyNotFoundException;

/**
 * Represents throttle config configuration.
 */
final readonly class ThrottleConfig
{
    /**
     * @param array<string, ThrottlePolicy> $policies
     */
    public function __construct(
        private string $storage,
        private array $policies,
    ) {}

    /**
     * Performs the storage operation.
     */
    public function storage(): string
    {
        return $this->storage;
    }

    /**
     * Performs the policy operation.
     */
    public function policy(string $name): ThrottlePolicy
    {
        return $this->policies[$name] ?? throw new ThrottlePolicyNotFoundException($name);
    }

    /**
     * @return array<string, ThrottlePolicy>
     */
    public function policies(): array
    {
        return $this->policies;
    }
}
