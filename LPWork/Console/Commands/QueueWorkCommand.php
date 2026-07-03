<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Queue\QueueManager;
use LPWork\Queue\QueueWorker;
use LPWork\Queue\QueueWorkerOptions;

/**
 * Handles the queue work command console command.
 */
final readonly class QueueWorkCommand implements Command, DescribesInput
{
    /**
     * Creates a new QueueWorkCommand instance.
     */
    public function __construct(
        private QueueWorker $worker,
        private QueueManager $queues,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'queue:work';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Process queued jobs.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $connection = $this->stringOption($input, 'connection') ?? $this->queues->defaultConnectionName();
        $queue = $this->stringOption($input, 'queue') ?? $this->queues->defaultQueueName();
        $result = $this->worker->work(new QueueWorkerOptions(
            connection: $connection,
            queue: $queue,
            once: $input->hasOption('once'),
            maxJobs: $this->intOption($input, 'max-jobs', 0),
            sleepSeconds: $this->intOption($input, 'sleep', 1),
            retryAfterSeconds: $this->nullableIntOption($input, 'retry-after'),
            retryDelaySeconds: $this->nullableIntOption($input, 'delay'),
        ));

        if ($result->failed > 0) {
            $this->messages->warning($output, 'Queue work complete with failed jobs.');
        } else {
            $this->messages->success($output, 'Queue work complete.');
        }

        $this->messages->summary($output, [
            'Connection' => $connection,
            'Queue' => $queue,
            'Processed' => $result->processed,
            'Failed' => $result->failed,
        ]);

        return $result->failed > 0 ? 1 : 0;
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
            ConsoleOption::value('connection', null, 'Queue connection to use.'),
            ConsoleOption::value('queue', null, 'Queue name to process.'),
            ConsoleOption::flag('once', null, 'Process one job and stop.'),
            ConsoleOption::value('max-jobs', null, 'Maximum jobs to reserve before stopping.'),
            ConsoleOption::value('sleep', null, 'Seconds to sleep when no job is available.'),
            ConsoleOption::value('retry-after', null, 'Seconds before a reserved job may be retried.'),
            ConsoleOption::value('delay', null, 'Seconds before a released job becomes available.'),
        ];
    }

    private function stringOption(Input $input, string $name): ?string
    {
        $value = $input->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function nullableIntOption(Input $input, string $name): ?int
    {
        $value = $input->option($name);

        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function intOption(Input $input, string $name, int $default): int
    {
        return $this->nullableIntOption($input, $name) ?? $default;
    }
}
