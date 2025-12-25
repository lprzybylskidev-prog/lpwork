<?php
declare(strict_types=1);

namespace Config;

use LPwork\Queue\QueueHandlerProviderInterface;

/**
 * Application-level queue handler provider.
 */
class QueueProvider implements QueueHandlerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        /**
         * @var array<string, class-string<\LPwork\Queue\Contract\QueueHandlerInterface>|callable> $handlers
         * Map queue name => handler (callable or QueueHandlerInterface class-string).
         */
        $handlers = [];

        return $handlers;
    }
}
