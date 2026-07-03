<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Defines the contract for command.
 */
interface Command
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string;

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string;

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int;
}
