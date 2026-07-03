<?php

declare(strict_types=1);

namespace LPWork\Logging;

use LPWork\Config\ArrayConfigReader;
use LPWork\Logging\Contracts\LogFormatter;
use LPWork\Logging\Exceptions\InvalidLogConfigException;
use LPWork\Logging\Exceptions\InvalidLogFormatterException;
use LPWork\Logging\Exceptions\MissingLogConfigException;
use LPWork\Logging\Formatters\JsonLogFormatter;
use LPWork\Logging\Formatters\LineLogFormatter;

/**
 * Creates log formatter factory instances from framework configuration.
 */
final readonly class LogFormatterFactory
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): LogFormatter
    {
        $format = $this->reader($config)->optionalString('format', $key) ?? 'line';

        return match ($format) {
            'line' => new LineLogFormatter(),
            'json' => new JsonLogFormatter(),
            default => throw new InvalidLogFormatterException($format),
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingLogConfigException => new MissingLogConfigException($key),
            invalidException: static fn(string $key): InvalidLogConfigException => new InvalidLogConfigException($key),
        );
    }
}
