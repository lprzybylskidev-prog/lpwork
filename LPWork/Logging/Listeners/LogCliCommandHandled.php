<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Kernels\Cli\Events\CliCommandHandled;
use LPWork\Logging\Contracts\Logger;

/**
 * Represents the log cli command handled framework component.
 */
final readonly class LogCliCommandHandled
{
    /**
     * Creates a new LogCliCommandHandled instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(CliCommandHandled $event): void
    {
        $this->logger->info('CLI command handled.', [
            'command' => $event->request->input()->command(),
            'exit_code' => $event->exitCode,
            'duration_ms' => $event->durationMs,
        ]);
    }
}
