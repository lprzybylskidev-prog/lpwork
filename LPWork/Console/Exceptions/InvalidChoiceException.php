<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use RuntimeException;

/**
 * Reports invalid choice exception failures.
 */
final class InvalidChoiceException extends RuntimeException
{
    /**
     * Creates a new InvalidChoiceException instance.
     */
    public function __construct(string $choice)
    {
        parent::__construct("Invalid console choice: {$choice}.");
    }
}
