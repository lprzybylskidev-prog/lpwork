<?php

declare(strict_types=1);

namespace LPWork\Session;

use LPWork\Config\NamedDriverConfig;
use LPWork\Config\NamedDriverConfigFactory;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Exceptions\InvalidSessionConfigException;
use LPWork\Session\Exceptions\InvalidSessionDriverException;
use LPWork\Session\Exceptions\MissingSessionConfigException;

/**
 * Coordinates configured session manager services.
 */
final class SessionManager
{
    /**
     * @var array<string, SessionDriver>
     */
    private array $drivers = [];

    private NamedDriverConfig $driverConfig;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private SessionDriverFactory $driverFactory = new SessionDriverFactory(),
    ) {
        $this->driverConfig = $this->driverConfig($config);
    }

    public function default(): SessionDriver
    {
        return $this->driver($this->defaultDriverName());
    }

    /**
     * Returns the configured session driver name used when no driver is requested explicitly.
     */
    public function defaultDriverName(): string
    {
        return $this->driverConfig->defaultName();
    }

    /**
     * Returns driver.
     */
    public function driver(string $name): SessionDriver
    {
        if (array_key_exists($name, $this->drivers)) {
            return $this->drivers[$name];
        }

        $config = $this->driverConfig->entry($name, static fn(string $name): InvalidSessionDriverException => new InvalidSessionDriverException($name));

        $this->drivers[$name] = $this->driverFactory->create($config, $this->driverConfig->entryKey($name));

        return $this->drivers[$name];
    }

    /**
     * Returns the configured driver type for a named session driver.
     */
    public function driverType(string $name): string
    {
        $config = $this->driverConfig->entry($name, static fn(string $name): InvalidSessionDriverException => new InvalidSessionDriverException($name));
        $driver = $config['driver'] ?? null;

        return is_string($driver) && $driver !== '' ? $driver : 'unknown';
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function driverConfig(array $config): NamedDriverConfig
    {
        return new NamedDriverConfigFactory()->create(
            config: $config,
            entriesKey: 'drivers',
            missingException: static fn(string $key): MissingSessionConfigException => new MissingSessionConfigException($key),
            invalidException: static fn(string $key): InvalidSessionConfigException => new InvalidSessionConfigException($key),
        );
    }
}
