<?php

declare(strict_types=1);

namespace LPWork\Queue\Exceptions;

use RuntimeException;

/**
 * Reports invalid queue storage record exception failures.
 */
final class InvalidQueueStorageRecordException extends RuntimeException
{
    /**
     * Creates a new InvalidQueueStorageRecordException instance.
     */
    public function __construct(string $field)
    {
        parent::__construct(sprintf('Queue storage record field is invalid: %s.', $field));
    }
}
