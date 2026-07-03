<?php

declare(strict_types=1);

namespace LPWork\Foundation;

/**
 * Represents the framework module catalog framework component.
 */
final readonly class FrameworkModuleCatalog
{
    /**
     * @var non-empty-list<string>
     */
    private const MODULE_KEYS = [
        'routing',
        'http',
        'middleware',
        'container',
        'config',
        'health',
        'views',
        'translation',
        'validation',
        'url',
        'security',
        'throttle',
        'session',
        'cache',
        'locks',
        'storage',
        'database',
        'migrations',
        'queue',
        'mail',
        'notifications',
        'broadcasting',
        'scheduler',
        'console',
        'completion',
        'file_creators',
        'maintenance',
        'events',
        'logging',
        'observability',
        'debugbar',
        'debug_dump',
        'errors',
        'testing',
    ];

    /**
     * @return non-empty-list<FrameworkModuleDefinition>
     */
    public function all(): array
    {
        return array_map(
            static fn(string $key): FrameworkModuleDefinition => new FrameworkModuleDefinition($key),
            self::MODULE_KEYS,
        );
    }

    /**
     * Performs the count operation.
     */
    public function count(): int
    {
        return count(self::MODULE_KEYS);
    }
}
