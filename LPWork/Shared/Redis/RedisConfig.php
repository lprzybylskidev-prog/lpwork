<?php

declare(strict_types=1);

namespace LPWork\Shared\Redis;

/**
 * Represents redis config configuration.
 */
final readonly class RedisConfig
{
    /**
     * Creates a new RedisConfig instance.
     */
    public function __construct(
        public string $host,
        public int $port,
        public ?string $password = null,
        public int $database = 0,
        public float $timeoutSeconds = 2.5,
        public string $prefix = '',
    ) {}
}
