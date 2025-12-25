<?php
declare(strict_types=1);

namespace LPwork\Queue\Contract;

/**
 * Contract for resolving queue drivers.
 */
interface QueueManagerInterface
{
    /**
     * @param string|null $queue
     *
     * @return QueueDriverInterface
     */
    public function queue(?string $queue = null): QueueDriverInterface;

    /**
     * @return string
     */
    public function defaultQueue(): string;
}
