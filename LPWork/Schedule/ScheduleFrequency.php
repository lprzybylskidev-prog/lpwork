<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use DateTimeImmutable;
use LPWork\Schedule\Exceptions\InvalidScheduleExpressionException;

/**
 * Represents the schedule frequency framework component.
 */
final readonly class ScheduleFrequency
{
    private function __construct(
        private string $expression,
    ) {}

    /**
     * Performs the cron operation.
     */
    public static function cron(string $expression): self
    {
        $parts = preg_split('/\s+/', trim($expression));

        if ($parts === false || count($parts) !== 5) {
            throw new InvalidScheduleExpressionException($expression);
        }

        return new self(implode(' ', $parts));
    }

    /**
     * Performs the every minute operation.
     */
    public static function everyMinute(): self
    {
        return new self('* * * * *');
    }

    /**
     * Performs the every minutes operation.
     */
    public static function everyMinutes(int $minutes): self
    {
        if ($minutes < 1 || $minutes > 59) {
            throw new InvalidScheduleExpressionException(sprintf('*/%d * * * *', $minutes));
        }

        return new self(sprintf('*/%d * * * *', $minutes));
    }

    /**
     * Performs the hourly operation.
     */
    public static function hourly(): self
    {
        return new self('0 * * * *');
    }

    /**
     * Performs the daily at operation.
     */
    public static function dailyAt(string $time): self
    {
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $matches) !== 1) {
            throw new InvalidScheduleExpressionException($time);
        }

        return new self(sprintf('%d %d * * *', (int) $matches[2], (int) $matches[1]));
    }

    /**
     * Performs the expression operation.
     */
    public function expression(): string
    {
        return $this->expression;
    }

    /**
     * Reports whether is due.
     */
    public function isDue(DateTimeImmutable $now): bool
    {
        [$minute, $hour, $day, $month, $weekday] = explode(' ', $this->expression);

        return $this->fieldMatches($minute, (int) $now->format('i'), 0, 59)
            && $this->fieldMatches($hour, (int) $now->format('G'), 0, 23)
            && $this->fieldMatches($day, (int) $now->format('j'), 1, 31)
            && $this->fieldMatches($month, (int) $now->format('n'), 1, 12)
            && $this->fieldMatches($weekday, (int) $now->format('w'), 0, 6);
    }

    private function fieldMatches(string $field, int $value, int $min, int $max): bool
    {
        foreach (explode(',', $field) as $part) {
            if ($this->partMatches($part, $value, $min, $max)) {
                return true;
            }
        }

        return false;
    }

    private function partMatches(string $part, int $value, int $min, int $max): bool
    {
        if ($part === '*') {
            return true;
        }

        if (preg_match('/^\*\/([1-9]\d*)$/', $part, $matches) === 1) {
            $step = (int) $matches[1];

            return $step > 0 && $value % $step === 0;
        }

        if (preg_match('/^(\d+)-(\d+)$/', $part, $matches) === 1) {
            $from = (int) $matches[1];
            $to = (int) $matches[2];

            return $from <= $to && $from >= $min && $to <= $max && $value >= $from && $value <= $to;
        }

        if (preg_match('/^\d+$/', $part) === 1) {
            $expected = (int) $part;

            return $expected >= $min && $expected <= $max && $expected === $value;
        }

        return false;
    }
}
