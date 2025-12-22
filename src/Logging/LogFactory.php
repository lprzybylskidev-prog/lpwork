<?php
declare(strict_types=1);

namespace LPwork\Logging;

use LPwork\Database\DatabaseConnectionManager;
use LPwork\Logging\Exception\LoggingConfigurationException;
use LPwork\Logging\Handler\DatabaseLogHandler;
use LPwork\Logging\Handler\RedisLogHandler;
use LPwork\Redis\RedisConnectionManager;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level as MonologLevel;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Builds PSR-3 loggers using Monolog based on configuration.
 */
class LogFactory
{
    /**
     * @param LogConfiguration           $configuration
     * @param RedisConnectionManager     $redisConnections
     * @param DatabaseConnectionManager  $databaseConnections
     * @param \Carbon\CarbonImmutable    $clock
     *
     * @return LoggerInterface
     */
    public function createDefault(
        LogConfiguration $configuration,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
        \Carbon\CarbonImmutable $clock,
    ): LoggerInterface {
        $defaultChannel = $configuration->defaultChannel();

        return $this->createChannel(
            $defaultChannel,
            $configuration,
            $redisConnections,
            $databaseConnections,
            $clock,
        );
    }

    /**
     * Creates logger instance for a given channel.
     *
     * @param string                     $name
     * @param LogConfiguration           $configuration
     * @param RedisConnectionManager     $redisConnections
     * @param DatabaseConnectionManager  $databaseConnections
     * @param \Carbon\CarbonImmutable    $clock
     *
     * @return LoggerInterface
     */
    public function createChannel(
        string $name,
        LogConfiguration $configuration,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
        \Carbon\CarbonImmutable $clock,
    ): LoggerInterface {
        $channelConfig = $configuration->channel($name);
        $driver = (string) ($channelConfig['driver'] ?? 'stderr');
        $level = $this->normalizeLevel((string) ($channelConfig['level'] ?? 'debug'));
        $bubble = (bool) ($channelConfig['bubble'] ?? true);

        if ($driver === 'stderr') {
            $handler = $this->createStreamHandler('php://stderr', $level, $bubble);
        } elseif ($driver === 'single') {
            $path = (string) ($channelConfig['path'] ?? '');

            if ($path === '') {
                throw new LoggingConfigurationException(
                    \sprintf('Logging channel "%s" requires "path" for single driver.', $name),
                );
            }

            $handler = $this->createStreamHandler($path, $level, $bubble);
        } elseif ($driver === 'redis') {
            $connectionName = (string) ($channelConfig['connection'] ?? 'default');
            $key = (string) ($channelConfig['key'] ?? 'logs');
            $redisClient = $redisConnections->get($connectionName)->client();
            $handler = new RedisLogHandler($redisClient, $key, $level, $bubble);
        } elseif ($driver === 'database') {
            $connectionName = (string) ($channelConfig['connection'] ?? 'default');
            $table = (string) ($channelConfig['table'] ?? 'logs');
            $handler = new DatabaseLogHandler(
                $databaseConnections->get($connectionName)->connection(),
                $table,
                $level,
                $bubble,
            );
        } else {
            throw new LoggingConfigurationException(
                \sprintf('Logging driver "%s" is not supported.', $driver),
            );
        }

        $logger = new Logger($name);
        $logger->setTimezone($clock->getTimezone());
        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * @param string      $path
     * @param MonologLevel $level
     * @param bool        $bubble
     *
     * @return HandlerInterface
     */
    private function createStreamHandler(
        string $path,
        MonologLevel $level,
        bool $bubble,
    ): HandlerInterface {
        return new StreamHandler($path, $level, $bubble);
    }

    /**
     * Normalizes string log level to Monolog Level.
     *
     * @param string $level
     *
     * @return MonologLevel
     */
    private function normalizeLevel(string $level): MonologLevel
    {
        return match (\strtolower($level)) {
            'debug' => MonologLevel::Debug,
            'info' => MonologLevel::Info,
            'notice' => MonologLevel::Notice,
            'warning' => MonologLevel::Warning,
            'error' => MonologLevel::Error,
            'critical' => MonologLevel::Critical,
            'alert' => MonologLevel::Alert,
            'emergency' => MonologLevel::Emergency,
            default => MonologLevel::Info,
        };
    }
}
