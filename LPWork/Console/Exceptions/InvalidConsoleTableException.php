<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid console table exception failures.
 */
final class InvalidConsoleTableException extends InvalidArgumentException
{
    /**
     * Performs the empty headers operation.
     */
    public static function emptyHeaders(): self
    {
        return new self('Console table must define at least one header.');
    }

    /**
     * Performs the row column count mismatch operation.
     */
    public static function rowColumnCountMismatch(int $rowIndex, int $expected, int $actual): self
    {
        return new self(sprintf(
            'Console table row %d has %d columns, expected %d.',
            $rowIndex,
            $actual,
            $expected,
        ));
    }
}
