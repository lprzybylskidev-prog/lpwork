<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use LPwork\Database\DatabaseConnectionManager;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\Exception\ErrorLogConfigurationException;
use LPwork\ErrorLog\Writer\DatabaseErrorLogWriter;
use LPwork\ErrorLog\Writer\FileErrorLogWriter;
use LPwork\ErrorLog\Writer\RedisErrorLogWriter;
use LPwork\Filesystem\FilesystemManager;
use LPwork\Redis\RedisConnectionManager;

/**
 * Creates error log writers based on configuration.
 */
final class ErrorLogWriterFactory
{
    /**
     * @param ErrorLogConfiguration   $config
     * @param DatabaseConnectionManager $databaseConnections
     * @param RedisConnectionManager  $redisConnections
     * @param FilesystemManager       $filesystemManager
     *
     * @return ErrorLogWriterInterface
     */
    public function create(
        ErrorLogConfiguration $config,
        DatabaseConnectionManager $databaseConnections,
        RedisConnectionManager $redisConnections,
        FilesystemManager $filesystemManager,
    ): ErrorLogWriterInterface {
        $driver = $config->driver();
        $config->assertSupportedDriver($driver);

        if ($driver === "file") {
            $fileConfig = $config->file();
            $mode = (string) ($fileConfig["mode"] ?? "daily");
            $directory = (string) ($fileConfig["directory"] ?? "");

            return new FileErrorLogWriter(
                $mode,
                $directory,
                $filesystemManager,
            );
        }

        if ($driver === "database") {
            $dbConfig = $config->database();
            $connection = (string) ($dbConfig["connection"] ?? "default");
            $table = (string) ($dbConfig["table"] ?? "errors");

            return new DatabaseErrorLogWriter(
                $databaseConnections,
                $connection,
                $table,
            );
        }

        if ($driver === "redis") {
            $redisConfig = $config->redis();
            $connection = (string) ($redisConfig["connection"] ?? "default");
            $prefix = (string) ($redisConfig["prefix"] ?? "errors:");
            $ttl = (int) ($redisConfig["ttl"] ?? 0);
            $maxEntries = (int) ($redisConfig["max_entries"] ?? 0);

            return new RedisErrorLogWriter(
                $redisConnections,
                $connection,
                $prefix,
                $ttl,
                $maxEntries,
            );
        }

        throw new ErrorLogConfigurationException(
            \sprintf('Error log driver "%s" is not supported.', $driver),
        );
    }
}
