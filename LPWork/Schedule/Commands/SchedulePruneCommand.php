<?php

declare(strict_types=1);

namespace LPWork\Schedule\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Schedule\SchedulePruner;

/**
 * Handles the schedule prune command console command.
 */
final readonly class SchedulePruneCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new SchedulePruneCommand instance.
     */
    public function __construct(
        private SchedulePruner $pruner,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'schedule:prune';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Prune expired schedule locks and retained run history.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $result = $this->pruner->prune();
        $this->messages->success($output, 'Schedule pruned.');
        $this->messages->summary($output, [
            'Locks' => $result['locks'],
            'Runs' => $result['runs'],
        ]);

        return 0;
    }

    /**
     * @return list<\LPWork\Console\ConsoleArgument>
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
            ConsoleOption::flag('force', description: 'Allow pruning schedule storage in production.'),
        ];
    }

    /**
     * @return list<string>
     */
    public function middleware(): array
    {
        return [
            ProductionSafetyMiddleware::class,
        ];
    }

    /**
     * Performs the production safety message operation.
     */
    public function productionSafetyMessage(): string
    {
        return 'Refusing to prune scheduler storage in production without --force.';
    }
}
