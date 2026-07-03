<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Represents the pending scheduled task framework component.
 */
final class PendingScheduledTask
{
    private ScheduleFrequency $frequency;

    private bool $withoutOverlapping = true;

    private ?string $queueConnection = null;

    private ?string $queue = null;

    private int $delaySeconds = 0;

    /**
     * @param list<string> $arguments
     * @param array<string, string|bool|int> $options
     */
    public function __construct(
        private readonly string $name,
        private readonly ScheduledTaskType $type,
        private readonly string $target,
        private readonly array $arguments = [],
        private readonly array $options = [],
        private readonly ?object $job = null,
    ) {
        $this->frequency = ScheduleFrequency::everyMinute();
    }

    /**
     * Performs the cron operation.
     */
    public function cron(string $expression): self
    {
        $this->frequency = ScheduleFrequency::cron($expression);

        return $this;
    }

    /**
     * Performs the every minute operation.
     */
    public function everyMinute(): self
    {
        $this->frequency = ScheduleFrequency::everyMinute();

        return $this;
    }

    /**
     * Performs the every minutes operation.
     */
    public function everyMinutes(int $minutes): self
    {
        $this->frequency = ScheduleFrequency::everyMinutes($minutes);

        return $this;
    }

    /**
     * Performs the hourly operation.
     */
    public function hourly(): self
    {
        $this->frequency = ScheduleFrequency::hourly();

        return $this;
    }

    /**
     * Performs the daily at operation.
     */
    public function dailyAt(string $time): self
    {
        $this->frequency = ScheduleFrequency::dailyAt($time);

        return $this;
    }

    /**
     * Returns allow overlapping.
     */
    public function allowOverlapping(): self
    {
        $this->withoutOverlapping = false;

        return $this;
    }

    /**
     * Performs the on queue operation.
     */
    public function onQueue(?string $connection = null, ?string $queue = null): self
    {
        $this->queueConnection = $connection;
        $this->queue = $queue;

        return $this;
    }

    /**
     * Performs the delay operation.
     */
    public function delay(int $seconds): self
    {
        $this->delaySeconds = max(0, $seconds);

        return $this;
    }

    /**
     * Performs the task operation.
     */
    public function task(): ScheduledTask
    {
        return new ScheduledTask(
            name: $this->name,
            type: $this->type,
            target: $this->target,
            frequency: $this->frequency,
            arguments: $this->arguments,
            options: $this->options,
            job: $this->job,
            withoutOverlapping: $this->withoutOverlapping,
            queueConnection: $this->queueConnection,
            queue: $this->queue,
            delaySeconds: $this->delaySeconds,
        );
    }
}
