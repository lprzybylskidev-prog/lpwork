<?php

declare(strict_types=1);

namespace LPWork\View\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports view render exception failures.
 */
final class ViewRenderException extends RuntimeException
{
    /**
     * Performs the failed operation.
     */
    public static function failed(string $path, ?Throwable $previous = null): self
    {
        return new self(sprintf('Could not render view file: %s.', $path), previous: $previous);
    }

    /**
     * Performs the section not started operation.
     */
    public static function sectionNotStarted(string $name): self
    {
        return new self(sprintf('Cannot end view section [%s] because it was not started.', $name));
    }

    /**
     * Returns value cannot be escaped.
     */
    public static function valueCannotBeEscaped(): self
    {
        return new self('View value cannot be escaped because it is not scalar or stringable.');
    }
}
