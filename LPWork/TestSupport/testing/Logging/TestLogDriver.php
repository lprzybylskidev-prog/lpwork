<?php

declare(strict_types=1);

namespace Tests\support\testing\Logging;

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogRecord;
use PHPUnit\Framework\Assert;

final class TestLogDriver implements LogDriver
{
    /**
     * @var list<LogRecord>
     */
    private array $records = [];

    public function save(LogRecord $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return list<LogRecord>
     */
    public function records(): array
    {
        return $this->records;
    }

    public function assertLogged(LogLevel $level, string $message): self
    {
        $matched = false;

        foreach ($this->records as $record) {
            if ($record->level === $level && $record->message === $message) {
                $matched = true;

                break;
            }
        }

        Assert::assertTrue($matched, sprintf('Log record [%s] with level [%s] was not saved.', $message, $level->value));

        return $this;
    }

    public function assertNothingLogged(): self
    {
        Assert::assertSame([], $this->records, 'Log records were saved unexpectedly.');

        return $this;
    }
}
