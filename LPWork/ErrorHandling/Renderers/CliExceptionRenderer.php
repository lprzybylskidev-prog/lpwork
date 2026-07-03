<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\ErrorHandling\Contracts\ExceptionRenderer;
use Throwable;

/**
 * Renders cli exception renderer output.
 */
final readonly class CliExceptionRenderer implements ExceptionRenderer
{
    /**
     * Creates a new CliExceptionRenderer instance.
     */
    public function __construct(
        private bool $debug = true,
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): string
    {
        if (!$this->debug) {
            return "Internal Server Error\n";
        }

        return sprintf(
            "%s: %s\n\nFile: %s:%d\n\nStack trace:\n%s\n",
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString(),
        );
    }
}
