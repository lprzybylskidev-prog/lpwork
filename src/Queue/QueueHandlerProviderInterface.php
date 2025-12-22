<?php
declare(strict_types=1);

namespace LPwork\Queue;

use LPwork\Queue\Contract\QueueHandlerInterface;

/**
 * Provides queue handlers registered by the application.
 */
interface QueueHandlerProviderInterface
{
    /**
     * @return array<string, class-string<QueueHandlerInterface>|callable>
     */
    public function getHandlers(): array;
}
