<?php

declare(strict_types=1);

namespace LPWork\Console;

/**
 * Represents the process environment framework component.
 */
final readonly class ProcessEnvironment
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $values = getenv();

        $environment = [];

        foreach ($values as $key => $value) {
            $environment[$key] = $value;
        }

        return $environment;
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $key): ?string
    {
        $value = getenv($key);

        return $value === false ? null : $value;
    }
}
