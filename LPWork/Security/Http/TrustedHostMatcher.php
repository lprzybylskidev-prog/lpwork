<?php

declare(strict_types=1);

namespace LPWork\Security\Http;

/**
 * Represents the trusted host matcher framework component.
 */
final readonly class TrustedHostMatcher
{
    /**
     * @param list<string> $patterns
     */
    public function __construct(private array $patterns) {}

    /**
     * Reports whether matches.
     */
    public function matches(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if ($this->matchesPattern($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $pattern, string $host): bool
    {
        if ($pattern === $host || $pattern === '*') {
            return true;
        }

        if (!str_contains($pattern, '*')) {
            return false;
        }

        $regex = '/^' . str_replace('\*', '[^.]+', preg_quote($pattern, '/')) . '$/i';

        return preg_match($regex, $host) === 1;
    }
}
