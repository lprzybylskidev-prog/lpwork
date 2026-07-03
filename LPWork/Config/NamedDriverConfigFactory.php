<?php

declare(strict_types=1);

namespace LPWork\Config;

use Closure;
use Throwable;

/**
 * Creates named driver config factory instances from framework configuration.
 */
final readonly class NamedDriverConfigFactory
{
    /**
     * @param array<array-key, mixed> $config
     * @param Closure(string): Throwable $missingException
     * @param Closure(string): Throwable $invalidException
     */
    public function create(
        array $config,
        string $entriesKey,
        Closure $missingException,
        Closure $invalidException,
        string $defaultKey = 'default',
    ): NamedDriverConfig {
        $reader = new ArrayConfigReader(
            config: $config,
            missingException: $missingException,
            invalidException: $invalidException,
        );

        return new NamedDriverConfig(
            default: $reader->string($defaultKey),
            entries: $reader->arrayMap($entriesKey),
            entriesKey: $entriesKey,
        );
    }
}
