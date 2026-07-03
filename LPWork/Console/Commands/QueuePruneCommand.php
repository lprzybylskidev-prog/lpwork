<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Queue\QueuePruner;

/**
 * Handles the queue prune command console command.
 */
final readonly class QueuePruneCommand implements Command, DescribesInput
{
    /**
     * Creates a new QueuePruneCommand instance.
     */
    public function __construct(
        private QueuePruner $pruner,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'queue:prune';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Prune retained completed and failed queue jobs.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $connection = $input->option('connection');
        $pruned = $this->pruner->prune(is_string($connection) && $connection !== '' ? $connection : null);

        $this->messages->success($output, 'Queue pruned.');
        $this->messages->summary($output, [
            'Connection' => is_string($connection) && $connection !== '' ? $connection : 'default',
            'Completed' => $pruned['completed'],
            'Failed' => $pruned['failed'],
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
            ConsoleOption::value('connection', null, 'Queue connection to prune.'),
        ];
    }
}
