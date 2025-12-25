<?php
declare(strict_types=1);

namespace LPwork\Mail;

use LPwork\Mail\Exception\MailConfigurationException;
use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration holder for mail transports.
 */
final class MailConfiguration
{
    use ConfigNormalizer;

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
        $this->defaultConnection = $this->stringVal(
            $config['default_connection'] ?? null,
            'mail.default_connection',
            'smtp',
            false,
        );
        $this->connections = (array) ($config['connections'] ?? []);

        if ($this->connections !== [] && !isset($this->connections[$this->defaultConnection])) {
            throw new MailConfigurationException(
                \sprintf('Default mail connection "%s" is not defined.', $this->defaultConnection),
            );
        }
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
