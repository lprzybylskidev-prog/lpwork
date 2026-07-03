<?php

declare(strict_types=1);

namespace LPWork\Schedule\Contracts;

use LPWork\Console\Output;
use LPWork\Schedule\ScheduledTask;
use LPWork\Schedule\ScheduledTaskResult;

/**
 * Defines the contract for scheduled task executor.
 */
interface ScheduledTaskExecutor
{
    /**
     * Reports whether supports.
     */
    public function supports(ScheduledTask $task): bool;

    /**
     * Runs execute.
     */
    public function execute(ScheduledTask $task, Output $output): ScheduledTaskResult;
}
