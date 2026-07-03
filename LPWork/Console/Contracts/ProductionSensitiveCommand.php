<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

/**
 * Defines the contract for production sensitive command.
 */
interface ProductionSensitiveCommand
{
    /**
     * Performs the production safety message operation.
     */
    public function productionSafetyMessage(): string;
}
