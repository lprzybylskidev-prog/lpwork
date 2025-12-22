<?php
declare(strict_types=1);

namespace LPwork\Queue;

use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\Queue\Contract\QueueHandlerInterface;
use Psr\Container\ContainerInterface;

/**
 * Simple queue worker processing jobs from a queue.
 */
class QueueWorker
{
    /**
     * @var QueueManager
     */
    private QueueManager $queues;

    /**
     * @var QueueHandlerProviderInterface
     */
    private QueueHandlerProviderInterface $handlerProvider;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var ErrorLoggerInterface
     */
    private ErrorLoggerInterface $errorLogger;

    /**
     * @param QueueManager                 $queues
     * @param QueueHandlerProviderInterface $handlerProvider
     * @param ContainerInterface           $container
     * @param ErrorLoggerInterface         $errorLogger
     */
    public function __construct(
        QueueManager $queues,
        QueueHandlerProviderInterface $handlerProvider,
        ContainerInterface $container,
        ErrorLoggerInterface $errorLogger,
    ) {
        $this->queues = $queues;
        $this->handlerProvider = $handlerProvider;
        $this->container = $container;
        $this->errorLogger = $errorLogger;
    }

    /**
     * @param string   $queue
     * @param int      $sleep
     * @param int|null $maxJobs
     * @param int|null $maxTimeSeconds
     * @param int      $tries
     * @param int      $backoffSeconds
     * @param bool     $once
     *
     * @return void
     */
    public function work(
        string $queue,
        int $sleep,
        ?int $maxJobs,
        ?int $maxTimeSeconds,
        int $tries,
        int $backoffSeconds,
        bool $once,
    ): void {
        $start = \time();
        $processed = 0;

        while (true) {
            if ($maxJobs !== null && $processed >= $maxJobs) {
                return;
            }

            if ($maxTimeSeconds !== null && \time() - $start >= $maxTimeSeconds) {
                return;
            }

            $job = $this->queues->queue($queue)->pop($sleep);

            if ($job === null) {
                if ($once) {
                    return;
                }

                continue;
            }

            $handler = $this->resolveHandler($job->queue());

            if ($handler === null) {
                $this->queues->queue($queue)->reject($job, false);
                $this->errorLogger->log(
                    new \RuntimeException(
                        \sprintf('No handler registered for queue "%s".', $job->queue()),
                    ),
                    ['queue' => $job->queue()],
                );
                continue;
            }

            try {
                $handler->handle($job);
                $this->queues->queue($queue)->ack($job);
            } catch (\Throwable $throwable) {
                $jobAttempts = $job->attempts() + 1;
                $maxAttempts = $job->maxAttempts();
                $shouldRetry =
                    $maxAttempts === null ? $jobAttempts < $tries : $jobAttempts < $maxAttempts;

                $this->errorLogger->log($throwable, [
                    'queue' => $job->queue(),
                    'job_id' => $job->id(),
                ]);

                if ($shouldRetry) {
                    \sleep($backoffSeconds);
                    $this->queues->queue($queue)->reject($job->withAttempts($jobAttempts), true);
                } else {
                    $this->queues->queue($queue)->reject($job, false);
                }
            }

            $processed++;

            if ($once) {
                return;
            }
        }
    }

    /**
     * @param string $queue
     *
     * @return QueueHandlerInterface|null
     */
    private function resolveHandler(string $queue): ?QueueHandlerInterface
    {
        $handlers = $this->handlerProvider->getHandlers();

        if (!isset($handlers[$queue])) {
            return null;
        }

        $handler = $handlers[$queue];

        if (\is_string($handler)) {
            /** @var QueueHandlerInterface $resolved */
            $resolved = $this->container->get($handler);

            return $resolved;
        }

        if (\is_callable($handler)) {
            return new class ($handler) implements QueueHandlerInterface {
                /**
                 * @var callable
                 */
                private $callable;

                /**
                 * @param callable $callable
                 */
                public function __construct(callable $callable)
                {
                    $this->callable = $callable;
                }

                /**
                 * @param QueueJob $job
                 *
                 * @return void
                 */
                public function handle(QueueJob $job): void
                {
                    \call_user_func($this->callable, $job);
                }
            };
        }
    }
}
