<?php
declare(strict_types=1);

namespace LPwork\Queue\Contract;

use LPwork\Queue\QueueJob;

/**
 * Serializes queue jobs for transport storage.
 */
interface JobSerializerInterface
{
    /**
     * @param QueueJob $job
     *
     * @return string
     */
    public function serialize(QueueJob $job): string;

    /**
     * @param string $payload
     *
     * @return QueueJob
     */
    public function deserialize(string $payload): QueueJob;
}
