<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Console\Command\QueueFlushCommand;
use LPwork\Console\Command\QueueWorkCommand;
use LPwork\Queue\Contract\JobSerializerInterface;
use LPwork\Queue\QueueConfiguration;
use LPwork\Queue\QueueDispatcher;
use LPwork\Queue\QueueHandlerProviderInterface;
use LPwork\Queue\QueueManager;
use LPwork\Queue\Serializer\JsonJobSerializer;
use LPwork\Queue\Messenger\QueueJobHandler;
use LPwork\Queue\Messenger\QueueSendersLocator;
use LPwork\Queue\Messenger\QueueServiceLocator;
use LPwork\Queue\Messenger\QueueTransport;
use LPwork\Redis\RedisConnectionManager;
use LPwork\Database\DatabaseConnectionManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

/**
 * Registers queue infrastructure and commands.
 */
final class QueueModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            QueueConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $configRepository,
            ): QueueConfiguration {
                /** @var array<string, mixed> $queueConfig */
                $queueConfig = $configRepository->get('queue', []);

                return new QueueConfiguration($queueConfig);
            }),
            JobSerializerInterface::class => \DI\autowire(JsonJobSerializer::class),
            QueueManager::class => \DI\autowire(QueueManager::class),
            QueueDispatcher::class => \DI\autowire(QueueDispatcher::class),
            QueueHandlerProviderInterface::class => \DI\get(\Config\QueueProvider::class),
            'queue.transports' => \DI\factory(static function (
                QueueConfiguration $queueConfiguration,
                QueueManager $queueManager,
            ): array {
                $transports = [];

                foreach ($queueConfiguration->queues() as $name => $queueConfig) {
                    $transports[$name] = new QueueTransport($queueManager->queue((string) $name));
                }

                return $transports;
            }),
            'queue.receivers' => \DI\get('queue.transports'),
            'queue.senders.container' => \DI\factory(static function (
                array $queueTransports,
            ): QueueServiceLocator {
                return new QueueServiceLocator($queueTransports);
            })->parameter('queueTransports', \DI\get('queue.transports')),
            'queue.retry.strategy_locator' => \DI\factory(static function (
                QueueConfiguration $queueConfiguration,
            ): QueueServiceLocator {
                $retry = $queueConfiguration->retry();
                $enabled = (bool) ($retry['enabled'] ?? true);

                if (!$enabled) {
                    return new QueueServiceLocator([]);
                }

                $maxRetries = (int) ($retry['max_retries'] ?? 3);
                $delayMs = (int) ($retry['delay_ms'] ?? 1000);
                $multiplier = (float) ($retry['multiplier'] ?? 2.0);
                $maxDelayMs = (int) ($retry['max_delay_ms'] ?? 0);

                $strategies = [];

                foreach (\array_keys($queueConfiguration->queues()) as $name) {
                    $strategies[(string) $name] = static function () use (
                        $maxRetries,
                        $delayMs,
                        $multiplier,
                        $maxDelayMs,
                    ): RetryStrategyInterface {
                        return new MultiplierRetryStrategy(
                            $maxRetries,
                            $delayMs,
                            $multiplier,
                            $maxDelayMs,
                        );
                    };
                }

                return new QueueServiceLocator($strategies);
            }),
            QueueSendersLocator::class => \DI\factory(static function (
                array $queueTransports,
            ): QueueSendersLocator {
                return new QueueSendersLocator($queueTransports);
            })->parameter('queueTransports', \DI\get('queue.transports')),
            HandlersLocator::class => \DI\get('queue.handlers.locator'),
            SendersLocatorInterface::class => \DI\get(QueueSendersLocator::class),
            QueueJobHandler::class => \DI\autowire(QueueJobHandler::class),
            'queue.handlers.locator' => \DI\factory(static function (
                QueueJobHandler $handler,
            ): HandlersLocator {
                return new HandlersLocator([
                    \LPwork\Queue\QueueJob::class => [$handler],
                ]);
            }),
            MessageBusInterface::class => \DI\factory(static function (
                SendersLocatorInterface $sendersLocator,
                HandlersLocator $handlersLocator,
            ): MessageBusInterface {
                return new MessageBus([
                    new SendMessageMiddleware($sendersLocator),
                    new HandleMessageMiddleware($handlersLocator),
                ]);
            }),
            QueueWorkCommand::class => \DI\factory(static function (
                MessageBusInterface $bus,
                array $queueReceivers,
                QueueServiceLocator $queueSendersContainer,
                QueueServiceLocator $retryStrategyLocator,
                LoggerInterface $logger,
            ): QueueWorkCommand {
                return new QueueWorkCommand(
                    $bus,
                    $queueReceivers,
                    $queueSendersContainer,
                    $retryStrategyLocator,
                    $logger,
                );
            })
                ->parameter('queueReceivers', \DI\get('queue.receivers'))
                ->parameter('queueSendersContainer', \DI\get('queue.senders.container'))
                ->parameter('retryStrategyLocator', \DI\get('queue.retry.strategy_locator')),
            QueueFlushCommand::class => \DI\autowire(QueueFlushCommand::class),
        ]);
    }
}
