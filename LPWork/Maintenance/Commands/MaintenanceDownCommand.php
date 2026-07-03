<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Commands;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Maintenance\MaintenanceMode;

/**
 * Handles the maintenance down command console command.
 */
final readonly class MaintenanceDownCommand implements Command, DescribesInput
{
    /**
     * Creates a new MaintenanceDownCommand instance.
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
        return 'maintenance:down';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Put the application into maintenance mode.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $retryAfter = $input->option('retry');

        if ($retryAfter !== null && !is_string($retryAfter)) {
            $this->messages->error($output, 'The --retry option must be a string value.');

            return 1;
        }

        $this->maintenance->activate($retryAfter);
        $this->messages->warning($output, 'Application is now in maintenance mode.');

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::value('retry', description: 'Retry-After header value for maintenance responses.'),
        ];
    }
}
