<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Contract;

use LPwork\ErrorLog\ErrorLogConfiguration;
use LPwork\Filesystem\Contract\FilesystemManagerInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;

/**
 * Contract for building error log writers.
 */
interface ErrorLogWriterFactoryInterface
{
    /**
     * @param ErrorLogConfiguration               $config
     * @param DatabaseConnectionManagerInterface  $databaseConnections
     * @param RedisConnectionManagerInterface     $redisConnections
     * @param FilesystemManagerInterface          $filesystemManager
     *
     * @return ErrorLogWriterInterface
     */
    public function create(
        ErrorLogConfiguration $config,
        DatabaseConnectionManagerInterface $databaseConnections,
        RedisConnectionManagerInterface $redisConnections,
        FilesystemManagerInterface $filesystemManager,
    ): ErrorLogWriterInterface;
}
