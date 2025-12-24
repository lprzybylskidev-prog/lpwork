<?php
declare(strict_types=1);

namespace LPwork\WebSocket;

/**
 * Holds WebSocket servers configuration.
 */
final class WebSocketConfiguration
{
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
        $this->defaultServer = (string) ($config['default_server'] ?? 'default');
        $this->servers = (array) ($config['servers'] ?? []);
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
