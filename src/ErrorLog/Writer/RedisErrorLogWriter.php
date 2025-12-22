<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Writer;

use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\ErrorLogEntry;
use LPwork\ErrorLog\Exception\ErrorLogWriteException;
use LPwork\Redis\RedisConnectionManager;
use Predis\ClientInterface;

/**
 * Stores error log entries in Redis as a capped list.
 */
class RedisErrorLogWriter implements ErrorLogWriterInterface
{
    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $connections;

    /**
     * @var string
     */
    private string $connectionName;

    /**
     * @var string
     */
    private string $prefix;

    /**
     * @var int
     */
    private int $ttl;

    /**
     * @var int
     */
    private int $maxEntries;

    /**
     * @param RedisConnectionManager $connections
     * @param string                 $connectionName
     * @param string                 $prefix
     * @param int                    $ttl
     * @param int                    $maxEntries
     */
    public function __construct(
        RedisConnectionManager $connections,
        string $connectionName,
        string $prefix,
        int $ttl,
        int $maxEntries,
    ) {
        $this->connections = $connections;
        $this->connectionName = $connectionName;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
        $this->maxEntries = $maxEntries;
    }

    /**
     * @inheritDoc
     */
    public function write(ErrorLogEntry $entry): void
    {
        $client = $this->client();
        $key = $this->prefix . 'log';
        $payload = \json_encode($entry->toArray());

        if ($payload === false) {
            throw new ErrorLogWriteException('Failed to encode error log entry.');
        }

        try {
            $client->lpush($key, [$payload]);

            if ($this->maxEntries > 0) {
                $client->ltrim($key, 0, $this->maxEntries - 1);
            }

            if ($this->ttl > 0) {
                $client->expire($key, $this->ttl);
            }
        } catch (\Throwable $throwable) {
            throw new ErrorLogWriteException(
                'Failed to write error log entry to Redis.',
                0,
                $throwable,
            );
        }
    }

    /**
     * @return ClientInterface
     */
    private function client(): ClientInterface
    {
        return $this->connections->get($this->connectionName)->client();
    }
}
