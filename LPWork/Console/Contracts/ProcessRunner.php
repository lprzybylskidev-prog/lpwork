<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

use LPWork\Console\Output;
use LPWork\Console\ProcessCommand;

/**
 * Defines the contract for process runner.
 */
interface ProcessRunner
{
    /**
     * Runs run.
     */
    public function run(ProcessCommand $command, Output $output): int;
}
