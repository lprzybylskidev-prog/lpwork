<?php
declare(strict_types=1);

namespace Config;

use LPwork\WebSocket\Contract\WebSocketComponentRegistryInterface;
use LPwork\WebSocket\Component\NullWebSocketComponent;
use Ratchet\MessageComponentInterface;

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
         * @var array<string, MessageComponentInterface> $components
         * Map server name => MessageComponentInterface implementation.
         */
        $components = [
            'default' => new NullWebSocketComponent(),
        ];

        return $components;
    }
}
