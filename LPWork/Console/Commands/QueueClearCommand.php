<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Queue\QueueManager;

/**
 * Handles the queue clear command console command.
 */
final readonly class QueueClearCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new QueueClearCommand instance.
     */
    public function __construct(
        private QueueManager $queues,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'queue:clear';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Clear pending and reserved queue jobs.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $connection = $input->option('connection');
        $queueName = $input->option('queue');
        $queue = is_string($connection) && $connection !== ''
            ? $this->queues->connection($connection)
            : $this->queues->default();

        $cleared = $queue->clear(is_string($queueName) && $queueName !== '' ? $queueName : $this->queues->defaultQueueName());
        $this->messages->success($output, 'Queue cleared.');
        $this->messages->summary($output, [
            'Connection' => is_string($connection) && $connection !== '' ? $connection : $this->queues->defaultConnectionName(),
            'Queue' => is_string($queueName) && $queueName !== '' ? $queueName : $this->queues->defaultQueueName(),
            'Jobs removed' => $cleared,
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
            ConsoleOption::value('connection', null, 'Queue connection to clear.'),
            ConsoleOption::value('queue', null, 'Queue name to clear.'),
            ConsoleOption::flag('force', description: 'Allow clearing queue jobs in production.'),
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
        return 'Refusing to clear queue jobs in production without --force.';
    }
}
