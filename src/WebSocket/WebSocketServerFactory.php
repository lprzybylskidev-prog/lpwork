<?php
declare(strict_types=1);

namespace LPwork\WebSocket;

use LPwork\WebSocket\Contract\WebSocketComponentRegistryInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;

/**
 * Builds Ratchet WebSocket servers.
 */
class WebSocketServerFactory
{
    /**
     * @param WebSocketConfiguration              $configuration
     * @param WebSocketComponentRegistryInterface $registry
     *
     * @return array<string, IoServer>
     */
    public function createAll(
        WebSocketConfiguration $configuration,
        WebSocketComponentRegistryInterface $registry,
    ): array {
        $servers = [];
        $components = $registry->getComponents();

        foreach ($configuration->servers() as $name => $serverConfig) {
            if (!(bool) ($serverConfig['enabled'] ?? true)) {
                continue;
            }

            if (!isset($components[$name])) {
                continue;
            }

            $servers[$name] = $this->createServer($serverConfig, $components[$name]);
        }

        return $servers;
    }

    /**
     * @param array<string, mixed>                $serverConfig
     * @param \Ratchet\MessageComponentInterface $component
     *
     * @return IoServer
     */
    public function createServer(
        array $serverConfig,
        \Ratchet\MessageComponentInterface $component,
    ): IoServer {
        $host = (string) ($serverConfig['host'] ?? '0.0.0.0');
        $port = (int) ($serverConfig['port'] ?? 8081);
        $wsServer = new WsServer($component);
        $httpServer = new HttpServer($wsServer);
        $socket = new SocketServer(\sprintf('%s:%d', $host, $port));

        return new IoServer($httpServer, $socket);
    }
}
