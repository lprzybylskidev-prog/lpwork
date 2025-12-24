<?php
declare(strict_types=1);

namespace LPwork\WebSocket\Component;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * No-op WebSocket component used as default placeholder.
 */
class NullWebSocketComponent implements MessageComponentInterface
{
    /**
     * @inheritDoc
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    public function onClose(ConnectionInterface $conn): void
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }
}
