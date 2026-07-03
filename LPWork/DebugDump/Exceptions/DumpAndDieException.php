<?php

declare(strict_types=1);

namespace LPWork\DebugDump\Exceptions;

use LPWork\DebugDump\DebugDumpRecord;
use RuntimeException;

/**
 * Reports dump and die exception failures.
 */
final class DumpAndDieException extends RuntimeException
{
    /**
     * Creates a new DumpAndDieException instance.
     */
    public function __construct(
        private readonly DebugDumpRecord $record,
    ) {
        parent::__construct('Debug dump terminated the request.');
    }

    /**
     * Performs the record operation.
     */
    public function record(): DebugDumpRecord
    {
        return $this->record;
    }
}
