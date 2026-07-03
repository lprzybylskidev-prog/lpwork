<?php

declare(strict_types=1);

namespace Tests\support\logging;

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\LogRecord;

final class InMemoryLogDriver implements LogDriver
{
    /**
     * @var list<LogRecord>
     */
    public array $records = [];

    public function save(LogRecord $record): void
    {
        $this->records[] = $record;
    }
}
