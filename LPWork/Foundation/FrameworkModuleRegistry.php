<?php

declare(strict_types=1);

namespace LPWork\Foundation;

/**
 * Stores and resolves framework module registry registrations.
 */
final class FrameworkModuleRegistry
{
    /**
     * @param list<string> $modules
     */
    public function __construct(private array $modules = []) {}

    /**
     * @param list<string> $modules
     */
    public function replace(array $modules): void
    {
        $this->modules = $modules;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Performs the count operation.
     */
    public function count(): int
    {
        return count($this->modules);
    }
}
