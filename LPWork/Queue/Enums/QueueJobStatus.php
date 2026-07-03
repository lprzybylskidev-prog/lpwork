<?php

declare(strict_types=1);

namespace LPWork\Queue\Enums;

/**
 * Enumerates the supported queue job status values.
 */
enum QueueJobStatus: string
{
    case Pending = 'pending';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Failed = 'failed';
}
