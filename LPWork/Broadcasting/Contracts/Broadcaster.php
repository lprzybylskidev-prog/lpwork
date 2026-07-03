<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Contracts;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\BroadcastResult;

/**
 * Defines the contract for broadcaster.
 */
interface Broadcaster
{
    /**
     * Runs broadcast.
     */
    public function broadcast(BroadcastMessage $message): BroadcastResult;
}
