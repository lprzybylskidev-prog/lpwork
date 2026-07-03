<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Events\EventDispatcher;
use LPWork\Logging\Contracts\Logger;
use LPWork\Queue\Events\QueueJobFailed;
use LPWork\Queue\Events\QueueJobProcessed;
use LPWork\Queue\Events\QueueJobProcessing;
use LPWork\Queue\Events\QueueJobReleased;
use Throwable;

/**
 * Represents the queue worker framework component.
 */
final readonly class QueueWorker
{
    /**
     * Creates a new QueueWorker instance.
     */
    public function __construct(
        private QueueManager $queues,
        private QueueJobRunner $runner,
        private ?EventDispatcher $events = null,
        private ?Logger $logger = null,
        private ?QueueDebugCollector $debugCollector = null,
    ) {}

    /**
     * Runs work.
     */
    public function work(QueueWorkerOptions $options): QueueWorkerResult
    {
        $connection = $this->queues->connection($options->connection);
        $processed = 0;
        $failed = 0;
        $handled = 0;

        do {
            $reserved = $connection->reserve(
                $options->queue,
                $options->retryAfterSeconds ?? $this->queues->retryAfterSeconds(),
            );

            if ($reserved === null) {
                if ($options->once) {
                    break;
                }

                sleep($options->sleepSeconds);
                continue;
            }

            $handled++;
            $started = hrtime(true);
            $this->events?->dispatch(new QueueJobProcessing($connection->name, $reserved));
            $this->logger?->info('Queue job processing.', $this->context($connection->name, $reserved));

            try {
                $this->runner->run($reserved);
                $connection->complete($reserved);
                $processed++;
                $durationMs = $this->durationMs($started);
                $this->record('processed', $connection->name, $reserved, $durationMs);
                $this->events?->dispatch(new QueueJobProcessed($connection->name, $reserved, $durationMs));
                $this->logger?->info('Queue job processed.', $this->context($connection->name, $reserved) + ['duration_ms' => $durationMs]);
            } catch (Throwable $throwable) {
                if ($reserved->attempts >= $reserved->payload->maxAttempts) {
                    $connection->fail($reserved, $throwable::class . ': ' . $throwable->getMessage());
                    $failed++;
                    $this->record('failed', $connection->name, $reserved, null, ['Exception' => $throwable::class]);
                    $this->events?->dispatch(new QueueJobFailed($connection->name, $reserved, $throwable));
                    $this->logger?->error('Queue job failed.', $this->context($connection->name, $reserved) + ['exception' => $throwable::class]);
                } else {
                    $delay = $options->retryDelaySeconds ?? $this->queues->retryDelaySeconds();
                    $connection->release($reserved, $delay);
                    $this->record('released', $connection->name, $reserved, null, [
                        'Delay seconds' => $delay,
                        'Exception' => $throwable::class,
                    ]);
                    $this->events?->dispatch(new QueueJobReleased($connection->name, $reserved, $delay, $throwable));
                    $this->logger?->warning('Queue job released for retry.', $this->context($connection->name, $reserved) + ['exception' => $throwable::class]);
                }
            }
        } while (!$options->once && ($options->maxJobs === 0 || $handled < $options->maxJobs));

        return new QueueWorkerResult($processed, $failed);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(string $connection, ReservedJob $job): array
    {
        return [
            'connection' => $connection,
            'queue' => $job->payload->queue,
            'job' => $job->payload->jobClass,
            'id' => $job->payload->id,
            'attempts' => $job->attempts,
        ];
    }

    private function durationMs(int $started): float
    {
        return round((hrtime(true) - $started) / 1_000_000, 3);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function record(string $status, string $connection, ReservedJob $job, ?float $durationMs = null, array $context = []): void
    {
        $this->debugCollector?->record(
            status: $status,
            connection: $connection,
            queue: $job->payload->queue,
            job: $job->payload->jobClass,
            id: $job->payload->id,
            durationMs: $durationMs,
            context: ['Attempts' => $job->attempts] + $context,
        );
    }
}
