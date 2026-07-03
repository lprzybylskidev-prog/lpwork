<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli\Events;

use LPWork\Requests\ConsoleRequest;
use Throwable;

/**
 * Represents the cli command failed framework component.
 */
final readonly class CliCommandFailed
{
    /**
     * Creates a new CliCommandFailed instance.
     */
    public function __construct(
        public ConsoleRequest $request,
        public int $exitCode,
        public float $durationMs,
        public Throwable $throwable,
    ) {}
}
