<?php

declare(strict_types=1);

use LPWork\Schedule\Exceptions\InvalidScheduleExpressionException;
use LPWork\Schedule\ScheduleFrequency;
use LPWork\Schedule\ScheduleRegistry;

it('resolves due scheduled tasks from cron expressions', function (): void {
    $schedule = new ScheduleRegistry();

    $schedule->command('nightly')->dailyAt('02:15');
    $schedule->command('frequent')->everyMinutes(5);

    expect(array_map(static fn($task): string => $task->name, $schedule->due(new DateTimeImmutable('2026-06-26 02:15:00'))))
        ->toBe(['nightly', 'frequent'])
        ->and(array_map(static fn($task): string => $task->name, $schedule->due(new DateTimeImmutable('2026-06-26 02:16:00'))))
        ->toBe([]);
});

it('rejects invalid schedule expressions', function (): void {
    expect(fn() => ScheduleFrequency::cron('* * *'))
        ->toThrow(InvalidScheduleExpressionException::class)
        ->and(fn() => ScheduleFrequency::everyMinutes(0))
        ->toThrow(InvalidScheduleExpressionException::class)
        ->and(fn() => ScheduleFrequency::dailyAt('25:00'))
        ->toThrow(InvalidScheduleExpressionException::class);
});
