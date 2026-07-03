<?php

declare(strict_types=1);

namespace LPWork\Environment\Exceptions;

use RuntimeException;

/**
 * Reports invalid line structure exception failures.
 */
final class InvalidLineStructureException extends RuntimeException
{
    /**
     * Creates a new InvalidLineStructureException instance.
     */
    public function __construct(string $line, int $lineNumber)
    {
        parent::__construct("Invalid .env structure on line {$lineNumber}: {$line}");
    }
}
