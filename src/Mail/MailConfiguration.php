<?php
declare(strict_types=1);

namespace LPwork\Mail;

use LPwork\Mail\Exception\MailConfigurationException;

/**
 * Typed configuration holder for mail transports.
 */
final class MailConfiguration
{
    /**
     * @var string
     */
    private string $defaultConnection;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $connections;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->defaultConnection = (string) ($config['default_connection'] ?? 'smtp');
        $this->connections = (array) ($config['connections'] ?? []);
    }

    /**
     * @return string
     */
    public function defaultConnection(): string
    {
        return $this->defaultConnection;
    }

    /**
     * @param string $name
     *
     * @return array<string, mixed>
     */
    public function connection(string $name): array
    {
        if (!isset($this->connections[$name])) {
            throw new MailConfigurationException(
                \sprintf('Mail connection "%s" is not defined.', $name),
            );
        }

        return (array) $this->connections[$name];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function connections(): array
    {
        return $this->connections;
    }
}
