<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigSource;

/**
 * Represents the config source definitions framework component.
 */
final readonly class ConfigSourceDefinitions implements ConfigSource
{
    /**
     * @param list<ConfigDefinition> $definitions
     */
    public function __construct(private array $definitions) {}

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function load(): array
    {
        $configs = [];

        foreach ($this->definitions as $definition) {
            $configs[$definition->key()] = array_replace_recursive(
                $configs[$definition->key()] ?? [],
                $definition->values(),
            );
        }

        return $configs;
    }
}
