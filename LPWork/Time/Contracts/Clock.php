<?php

declare(strict_types=1);

namespace LPWork\Time\Contracts;

use DateTimeImmutable;

/**
 * Defines the contract for clock.
 */
interface Clock
{
    /**
     * Performs the now operation.
     */
    public function now(): DateTimeImmutable;
}
