<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

use LPWork\Console\Input;

/**
 * Defines the contract for conditional production sensitive command.
 */
interface ConditionalProductionSensitiveCommand
{
    /**
     * Performs the production safety applies operation.
     */
    public function productionSafetyApplies(Input $input): bool;
}
