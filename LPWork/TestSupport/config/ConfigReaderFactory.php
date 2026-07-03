<?php

declare(strict_types=1);

namespace Tests\support\config;

use LPWork\Config\ArrayConfigReader;
use RuntimeException;

final readonly class ConfigReaderFactory
{
    /**
     * @param array<array-key, mixed> $config
     */
    public static function create(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): RuntimeException => new RuntimeException("missing: {$key}"),
            invalidException: static fn(string $key): RuntimeException => new RuntimeException("invalid: {$key}"),
        );
    }
}
