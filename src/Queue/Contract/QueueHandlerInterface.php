<?php
declare(strict_types=1);

namespace LPwork\Queue\Contract;

use LPwork\Queue\QueueJob;

/**
 * Handles queued jobs.
 */
interface QueueHandlerInterface
{
    /**
     * @param QueueJob $job
     *
     * @return void
     */
    public function handle(QueueJob $job): void;
}
