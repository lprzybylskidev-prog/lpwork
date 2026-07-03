<?php

declare(strict_types=1);

namespace LPWork\Shared\Redis;

use InvalidArgumentException;
use LPWork\Config\ArrayConfigReader;

/**
 * Creates redis config factory instances from framework configuration.
 */
final readonly class RedisConfigFactory
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function create(ArrayConfigReader $reader, array $config, string $key): RedisConfig
    {
        return new RedisConfig(
            host: $reader->string('host', "{$key}.host"),
            port: $reader->int('port', "{$key}.port"),
            password: $this->optionalNonEmpty($reader, 'password', "{$key}.password"),
            database: $reader->int('database', "{$key}.database"),
            timeoutSeconds: $this->timeout($config, $key),
            prefix: $reader->optionalString('prefix', "{$key}.prefix", allowEmpty: true) ?? '',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function timeout(array $config, string $key): float
    {
        $value = $config['timeout_seconds'] ?? 2.5;

        if (!is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException(sprintf('Invalid Redis timeout configuration value [%s.timeout_seconds].', $key));
        }

        return (float) $value;
    }

    private function optionalNonEmpty(ArrayConfigReader $reader, string $name, string $key): ?string
    {
        $value = $reader->optionalString($name, $key, allowEmpty: true);

        return $value === '' ? null : $value;
    }
}
