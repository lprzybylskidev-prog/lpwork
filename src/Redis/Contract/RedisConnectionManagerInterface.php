<?php
declare(strict_types=1);

namespace LPwork\Redis\Contract;

/**
 * Contract for resolving named Redis connections.
 */
interface RedisConnectionManagerInterface
{
    /**
     * @param string|null $name
     *
     * @return RedisConnectionInterface
     */
    public function get(?string $name = null): RedisConnectionInterface;
}
