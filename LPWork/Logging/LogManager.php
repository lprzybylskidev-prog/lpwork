<?php

declare(strict_types=1);

namespace LPWork\Logging;

use LPWork\Config\ArrayConfigReader;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\Exceptions\InvalidLogChannelException;
use LPWork\Logging\Exceptions\InvalidLogConfigException;
use LPWork\Logging\Exceptions\MissingLogConfigException;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Coordinates configured log manager services.
 */
final class LogManager
{
    /**
     * @var array<string, Logger>
     */
    private array $channels = [];

    private ArrayConfigReader $reader;

    private LogDriverFactory $driverFactory;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly string $basePath,
        ?LogDriverFactory $driverFactory = null,
        private readonly Clock $clock = new SystemClock(),
    ) {
        $this->reader = new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingLogConfigException => new MissingLogConfigException($key),
            invalidException: static fn(string $key): InvalidLogConfigException => new InvalidLogConfigException($key),
        );
        $this->driverFactory = $driverFactory ?? new LogDriverFactory($this->basePath);
    }

    public function default(): Logger
    {
        $default = $this->reader->string('default');

        return $this->channel($default);
    }

    /**
     * Returns channel.
     */
    public function channel(string $name): Logger
    {
        if (array_key_exists($name, $this->channels)) {
            return $this->channels[$name];
        }

        $channels = $this->reader->arrayMap('channels');

        if (!array_key_exists($name, $channels)) {
            throw new InvalidLogChannelException($name);
        }

        $channel = $this->makeChannel($name, $channels[$name]);
        $this->channels[$name] = $channel;

        return $channel;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function makeChannel(string $name, array $config): Logger
    {
        $driver = $this->reader($config)->string('driver', "channels.{$name}.driver");

        if ($driver === 'stack') {
            return $this->stackChannel($name, $config);
        }

        return new LogChannel(
            name: $name,
            driver: $this->driverFactory->create($config, "channels.{$name}"),
            minimumLevel: $this->minimumLevel($name, $config),
            clock: $this->clock,
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function stackChannel(string $name, array $config): StackLogChannel
    {
        $channelNames = $this->reader($config)->stringList('channels', "channels.{$name}.channels");
        $channels = [];

        foreach ($channelNames as $channelName) {
            $channels[] = $this->channel($channelName);
        }

        return new StackLogChannel($channels);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function minimumLevel(string $name, array $config): LogLevel
    {
        $level = $this->reader($config)->optionalString('level', "channels.{$name}.level");

        if ($level === null) {
            return LogLevel::Debug;
        }

        $logLevel = LogLevel::tryFrom($level);

        if ($logLevel === null) {
            throw new InvalidLogConfigException("channels.{$name}.level");
        }

        return $logLevel;
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
