<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Console\Output;
use LPWork\Events\EventDispatcher;
use LPWork\Locks\AtomicLockManager;
use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Logging\Contracts\Logger;
use LPWork\Schedule\Events\ScheduledTaskFailed;
use LPWork\Schedule\Events\ScheduledTaskSkipped;
use LPWork\Schedule\Events\ScheduledTaskStarting;
use LPWork\Schedule\Events\ScheduledTaskSucceeded;
use LPWork\Time\Contracts\Clock;
use Throwable;

/**
 * Represents the schedule runner framework component.
 */
final readonly class ScheduleRunner
{
    /**
     * Creates a new ScheduleRunner instance.
     */
    public function __construct(
        private ScheduleRegistry $schedule,
        private ScheduledTaskExecutorRegistry $executors,
        private ScheduleStoreFactory $stores,
        private AtomicLockManager $locks,
        private Clock $clock,
        private int $lockTtlSeconds,
        private ?EventDispatcher $events = null,
        private ?Logger $logger = null,
        private ?ScheduleDebugCollector $debugCollector = null,
    ) {}

    /**
     * Runs run.
     */
    public function run(ScheduleRunOptions $options, Output $output): ScheduleRunResult
    {
        $tasks = $options->force ? $this->schedule->all() : $this->schedule->due($this->clock->now());

        if ($options->task !== null) {
            $tasks = array_values(array_filter($tasks, static fn(ScheduledTask $task): bool => $task->name === $options->task));
        }

        $ran = 0;
        $skipped = 0;
        $failed = 0;

        if ($tasks === []) {
            return new ScheduleRunResult(0, 0, 0, 0);
        }

        $store = $this->stores->create();

        foreach ($tasks as $task) {
            $lock = $task->withoutOverlapping ? $this->locks->lock($task->lockName(), $this->lockTtlSeconds) : null;

            if ($lock !== null && !$lock->acquire()) {
                $skipped++;
                $this->debugCollector?->record('skipped', $task, context: ['Reason' => 'locked']);
                $this->events?->dispatch(new ScheduledTaskSkipped($task, 'locked'));
                $this->logger?->info('Scheduled task skipped because it is locked.', $this->context($task));
                $store->recordRun($task, 'skipped', $this->now(), $this->now(), null, 'Task is locked.');
                $output->writeln(sprintf('Skipped [%s]: locked.', $task->name));

                continue;
            }

            $started = $this->now();
            $startedHr = hrtime(true);
            $ran++;
            $this->debugCollector?->record('starting', $task);
            $this->events?->dispatch(new ScheduledTaskStarting($task));
            $this->logger?->info('Scheduled task starting.', $this->context($task));

            try {
                $result = $this->executors->forTask($task)->execute($task, $output);
                $finished = $this->now();

                if ($result->exitCode === 0) {
                    $durationMs = $this->durationMs($startedHr);
                    $this->debugCollector?->record('succeeded', $task, $durationMs, ['Message' => $result->message]);
                    $this->events?->dispatch(new ScheduledTaskSucceeded($task, $durationMs));
                    $this->logger?->info('Scheduled task succeeded.', $this->context($task) + ['duration_ms' => $durationMs]);
                    $store->recordRun($task, 'succeeded', $started, $finished, $result->exitCode, $result->message);
                    $output->writeln(sprintf('Ran [%s]: %s', $task->name, $result->message));
                } else {
                    $failed++;
                    $this->debugCollector?->record('failed', $task, null, [
                        'Exit code' => $result->exitCode,
                        'Message' => $result->message,
                    ]);
                    $store->recordRun($task, 'failed', $started, $finished, $result->exitCode, $result->message);
                    $this->logger?->warning('Scheduled task exited with a non-zero code.', $this->context($task) + ['exit_code' => $result->exitCode]);
                    $output->error(sprintf('Failed [%s]: %s', $task->name, $result->message));
                }
            } catch (Throwable $throwable) {
                $failed++;
                $this->debugCollector?->record('failed', $task, null, ['Exception' => $throwable::class]);
                $this->events?->dispatch(new ScheduledTaskFailed($task, $throwable));
                $this->logger?->error('Scheduled task failed.', $this->context($task) + ['exception' => $throwable::class]);
                $store->recordRun($task, 'failed', $started, $this->now(), 1, $throwable::class . ': ' . $throwable->getMessage());
                $output->error(sprintf('Failed [%s]: %s', $task->name, $throwable->getMessage()));
            } finally {
                if ($lock instanceof AtomicLock) {
                    $lock->release();
                }
            }
        }

        return new ScheduleRunResult(count($tasks), $ran, $skipped, $failed);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(ScheduledTask $task): array
    {
        return [
            'task' => $task->name,
            'type' => $task->type->value,
            'target' => $task->target,
        ];
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private function durationMs(int $started): float
    {
        return round((hrtime(true) - $started) / 1_000_000, 3);
    }
}
