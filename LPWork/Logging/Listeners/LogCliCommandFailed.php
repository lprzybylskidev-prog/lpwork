<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Kernels\Cli\Events\CliCommandFailed;
use LPWork\Logging\Contracts\Logger;

/**
 * Represents the log cli command failed framework component.
 */
final readonly class LogCliCommandFailed
{
    /**
     * Creates a new LogCliCommandFailed instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(CliCommandFailed $event): void
    {
        $this->logger->error('CLI command failed.', [
            'command' => $event->request->input()->command(),
            'exit_code' => $event->exitCode,
            'duration_ms' => $event->durationMs,
            'exception' => $event->throwable::class,
        ]);
    }
}
