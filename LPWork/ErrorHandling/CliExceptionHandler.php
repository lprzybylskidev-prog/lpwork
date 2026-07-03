<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling;

use LPWork\ErrorHandling\Contracts\ExceptionRenderer;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use Throwable;

/**
 * Represents the cli exception handler framework component.
 */
final class CliExceptionHandler
{
    /**
     * Creates a new CliExceptionHandler instance.
     */
    public function __construct(
        private readonly ExceptionReporter $reporter,
        private readonly ExceptionRenderer $renderer,
    ) {}

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(): void
    {
        set_exception_handler(function (Throwable $throwable): void {
            echo $this->handle($throwable);
        });
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Throwable $throwable): string
    {
        $this->report($throwable);

        return $this->render($throwable);
    }

    private function report(Throwable $throwable): void
    {
        $this->reporter->report($throwable);
    }

    private function render(Throwable $throwable): string
    {
        return $this->renderer->render($throwable);
    }
}
