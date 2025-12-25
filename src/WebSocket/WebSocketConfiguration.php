<?php
declare(strict_types=1);

namespace LPwork\WebSocket;

use LPwork\Config\Exception\InvalidConfigurationException;
use LPwork\Config\Support\ConfigNormalizer;

/**
 * Holds WebSocket servers configuration.
 */
final class WebSocketConfiguration
{
    use ConfigNormalizer;

    /**
     * @var string
     */
    private string $defaultServer;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $servers;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->defaultServer = $this->stringVal(
            $config['default_server'] ?? null,
            'websocket.default_server',
            'default',
            false,
        );
        $this->servers = (array) ($config['servers'] ?? []);

        if ($this->servers !== [] && !isset($this->servers[$this->defaultServer])) {
            throw new InvalidConfigurationException(
                \sprintf('Default WebSocket server "%s" is not defined.', $this->defaultServer),
            );
        }
    }

    /**
     * @return string
     */
    public function defaultServer(): string
    {
        return $this->defaultServer;
    }

    /**
     * @param string|null $name
     *
     * @return array<string, mixed>
     */
    public function server(?string $name = null): array
    {
        $target = $name ?? $this->defaultServer;

        return (array) ($this->servers[$target] ?? []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function servers(): array
    {
        return $this->servers;
    }
}
