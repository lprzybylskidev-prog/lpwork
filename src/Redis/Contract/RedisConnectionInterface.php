<?php
declare(strict_types=1);

namespace LPwork\Redis\Contract;

use Predis\ClientInterface;

/**
 * Represents a Redis connection abstraction.
 */
interface RedisConnectionInterface
{
    /**
     * Returns underlying Redis client instance.
     *
     * @return ClientInterface
     */
    public function client(): ClientInterface;
}
