<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli\Events;

use LPWork\Requests\ConsoleRequest;

/**
 * Represents the cli command handled framework component.
 */
final readonly class CliCommandHandled
{
    /**
     * Creates a new CliCommandHandled instance.
     */
    public function __construct(
        public ConsoleRequest $request,
        public int $exitCode,
        public float $durationMs,
    ) {}
}
