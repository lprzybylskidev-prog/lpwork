<?php

declare(strict_types=1);

namespace LPWork\Config;

use Closure;
use Throwable;

/**
 * Represents named driver config configuration.
 */
final readonly class NamedDriverConfig
{
    /**
     * @param array<string, array<array-key, mixed>> $entries
     */
    public function __construct(
        private string $default,
        private array $entries,
        private string $entriesKey,
    ) {}

    /**
     * Returns default name.
     */
    public function defaultName(): string
    {
        return $this->default;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->entries);
    }

    /**
     * @param Closure(string): Throwable $notConfigured
     *
     * @return array<array-key, mixed>
     */
    public function entry(string $name, Closure $notConfigured): array
    {
        if (!array_key_exists($name, $this->entries)) {
            throw $notConfigured($name);
        }

        return $this->entries[$name];
    }

    /**
     * Performs the entry key operation.
     */
    public function entryKey(string $name): string
    {
        return "{$this->entriesKey}.{$name}";
    }
}
