<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Maintenance\MaintenanceMode;

/**
 * Handles the maintenance up command console command.
 */
final readonly class MaintenanceUpCommand implements Command
{
    /**
     * Creates a new MaintenanceUpCommand instance.
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
        return 'maintenance:up';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Take the application out of maintenance mode.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->maintenance->deactivate();
        $this->messages->success($output, 'Application is now live.');

        return 0;
    }
}
