<?php
declare(strict_types=1);

namespace Config;

use LPwork\WebSocket\Contract\WebSocketComponentRegistryInterface;
use LPwork\WebSocket\Component\NullWebSocketComponent;

/**
 * Application-level WebSocket component provider.
 */
class WebSocketProvider implements WebSocketComponentRegistryInterface
{
    /**
     * @inheritDoc
     */
    public function getComponents(): array
    {
        /**
         * @var array<string, \Ratchet\MessageComponentInterface> $components
         * Map server name => MessageComponentInterface implementation.
         */
        $components = [
            'default' => new NullWebSocketComponent(),
        ];

        return $components;
    }
}
