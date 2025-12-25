<?php
declare(strict_types=1);

namespace LPwork\Queue;

/**
 * Provides queue handlers registered by the application.
 */
interface QueueHandlerProviderInterface
{
    /**
        * @return array<string, class-string<\LPwork\Queue\Contract\QueueHandlerInterface>|callable>
     */
    public function getHandlers(): array;
}
