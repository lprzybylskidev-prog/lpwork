<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

use LPwork\Http\Session\Exception\SessionConfigurationException;
use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed session configuration holder.
 */
final class SessionConfiguration
{
    use ConfigNormalizer;

    /**
     * @var string
     */
    private string $driver;

    /**
     * @var int
     */
    private int $lifetime;

    /**
     * @var array<string, mixed>
     */
    private array $cookie;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $drivers;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->driver = $this->stringVal($config['driver'] ?? null, 'session.driver', 'php', false);
        $this->lifetime = $this->intVal($config['lifetime'] ?? null, 'session.lifetime', 7200, 1);
        $this->cookie = (array) ($config['cookie'] ?? []);
        $this->drivers = (array) ($config['drivers'] ?? []);
    }

    /**
     * Returns configured driver name.
     *
     * @return string
     */
    public function driver(): string
    {
        return $this->driver;
    }

    /**
     * Returns session lifetime in seconds.
     *
     * @return int
     */
    public function lifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Returns cookie parameters array.
     *
     * @return array<string, mixed>
     */
    public function cookie(): array
    {
        return $this->cookie;
    }

    /**
     * Returns config for given driver.
     *
     * @param string $name
     *
     * @return array<string, mixed>
     */
    public function driverConfig(string $name): array
    {
        if (!isset($this->drivers[$name])) {
            throw new SessionConfigurationException(
                \sprintf('Session driver "%s" is not configured.', $name),
            );
        }

        return (array) $this->drivers[$name];
    }
}
