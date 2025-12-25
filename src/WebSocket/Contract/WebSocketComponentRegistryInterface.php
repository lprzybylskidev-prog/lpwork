<?php
declare(strict_types=1);

namespace LPwork\WebSocket\Contract;

/**
 * Provides WebSocket components keyed by server name.
 */
interface WebSocketComponentRegistryInterface
{
    /**
     * @return array<string, \Ratchet\MessageComponentInterface>
     */
    public function getComponents(): array;
}
