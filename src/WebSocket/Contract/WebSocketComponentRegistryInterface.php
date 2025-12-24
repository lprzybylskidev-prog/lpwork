<?php
declare(strict_types=1);

namespace LPwork\WebSocket\Contract;

use Ratchet\MessageComponentInterface;

/**
 * Provides WebSocket components keyed by server name.
 */
interface WebSocketComponentRegistryInterface
{
    /**
     * @return array<string, MessageComponentInterface>
     */
    public function getComponents(): array;
}
