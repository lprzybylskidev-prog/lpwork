<?php
declare(strict_types=1);

namespace LPwork\Redis;

use LPwork\Redis\Contract\RedisConnectionInterface;
use Predis\ClientInterface;
use Predis\Client;

/**
 * Predis-based Redis connection implementation.
 */
class PredisConnection implements RedisConnectionInterface
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @param RedisConfig $config
     */
    public function __construct(RedisConfig $config)
    {
        $this->client = new Client($config->toParameters(), $config->toOptions());
    }

    /**
     * @inheritDoc
     */
    public function client(): ClientInterface
    {
        return $this->client;
    }
}
