<?php
declare(strict_types=1);

namespace LPwork\Queue;

use LPwork\Queue\Exception\QueueConfigurationException;

/**
 * Typed configuration holder for queue definitions.
 */
final class QueueConfiguration
{
    /**
     * @var string
     */
    private string $defaultQueue;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $queues;

    /**
     * @var array<string, mixed>
     */
    private array $retry;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->defaultQueue = (string) ($config['default_queue'] ?? 'default');
        $this->queues = (array) ($config['queues'] ?? []);
        $this->retry = (array) ($config['retry'] ?? []);
    }

    /**
     * @return string
     */
    public function defaultQueue(): string
    {
        return $this->defaultQueue;
    }

    /**
     * @param string $queue
     *
     * @return array<string, mixed>
     */
    public function queue(string $queue): array
    {
        if (!isset($this->queues[$queue])) {
            throw new QueueConfigurationException(\sprintf('Queue "%s" is not defined.', $queue));
        }

        return (array) $this->queues[$queue];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queues(): array
    {
        return $this->queues;
    }

    /**
     * @return array<string, mixed>
     */
    public function retry(): array
    {
        return $this->retry;
    }
}
