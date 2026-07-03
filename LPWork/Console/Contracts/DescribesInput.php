<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleOption;

/**
 * Defines the contract for describes input.
 */
interface DescribesInput
{
    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array;

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array;
}
