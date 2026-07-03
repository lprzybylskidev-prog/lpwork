<?php

declare(strict_types=1);

namespace Tests\support\testing\Logging;

use Closure;
use DateTimeImmutable;
use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogRecord;
use PHPUnit\Framework\Assert;

final readonly class LogDriverContract
{
    /**
     * @param Closure(): string $contents
     */
    public function __construct(
        private LogDriver $driver,
        private Closure $contents,
    ) {}

    public function verifiesRecordsArePersisted(): void
    {
        $this->driver->save(new LogRecord(
            channel: 'contract',
            level: LogLevel::Info,
            message: 'Driver contract record',
            context: ['id' => 15],
            datetime: new DateTimeImmutable('2026-06-20 12:00:00'),
        ));

        $contents = ($this->contents)();

        Assert::assertStringContainsString('Driver contract record', $contents);
        Assert::assertStringContainsString('contract', $contents);
    }
}
