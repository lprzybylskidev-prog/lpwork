<?php
declare(strict_types=1);

namespace LPwork\Queue\Messenger;

use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\Queue\Contract\QueueHandlerInterface;
use LPwork\Queue\QueueHandlerProviderInterface;
use LPwork\Queue\QueueJob;
use Psr\Container\ContainerInterface;

/**
 * Messenger handler dispatching QueueJob to application handlers.
 */
class QueueJobHandler
{
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
     * @param QueueHandlerProviderInterface $handlerProvider
     * @param ContainerInterface            $container
     * @param ErrorLoggerInterface          $errorLogger
     */
    public function __construct(
        QueueHandlerProviderInterface $handlerProvider,
        ContainerInterface $container,
        ErrorLoggerInterface $errorLogger,
    ) {
        $this->handlerProvider = $handlerProvider;
        $this->container = $container;
        $this->errorLogger = $errorLogger;
    }

    /**
     * @param QueueJob $job
     *
     * @return void
     */
    public function __invoke(QueueJob $job): void
    {
        $handlers = $this->handlerProvider->getHandlers();
        $queue = $job->queue();

        if (!isset($handlers[$queue])) {
            $this->errorLogger->log(
                new \RuntimeException(\sprintf('No handler registered for queue "%s".', $queue)),
                ['queue' => $queue, 'job_id' => $job->id()],
            );

            return;
        }

        $handler = $handlers[$queue];

        if (\is_string($handler)) {
            /** @var QueueHandlerInterface $resolved */
            $resolved = $this->container->get($handler);
            $resolved->handle($job);

            return;
        }

        if (\is_callable($handler)) {
            $handler($job);
        }
    }
}
