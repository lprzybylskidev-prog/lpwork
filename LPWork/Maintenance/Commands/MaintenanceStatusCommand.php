<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Maintenance\MaintenanceMode;

/**
 * Handles the maintenance status command console command.
 */
final readonly class MaintenanceStatusCommand implements Command
{
    /**
     * Creates a new MaintenanceStatusCommand instance.
     */
    public function __construct(
        private MaintenanceMode $maintenance,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'maintenance:status';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Show whether the application is in maintenance mode.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $state = $this->maintenance->state();

        if (!$state->isActive()) {
            $this->messages->success($output, 'Maintenance mode is inactive.');

            return 0;
        }

        $this->messages->warning($output, 'Maintenance mode is active.');
        $this->messages->summary($output, [
            'Retry-After' => $state->retryAfter(),
        ]);

        return 0;
    }
}
