<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Console\Output;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Schedule\ScheduleRegistry;
use LPWork\Schedule\ScheduleRunner;
use LPWork\Schedule\ScheduleRunOptions;

/**
 * Represents the scheduler health check framework component.
 */
final readonly class SchedulerHealthCheck implements HealthCheck
{
    /**
     * Creates a new SchedulerHealthCheck instance.
     */
    public function __construct(
        private ScheduleRegistry $schedule,
        private ScheduleRunner $runner,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'scheduler';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            return HealthCheckResult::unhealthy($this->name(), 'Could not open scheduler health output stream.');
        }

        $result = $this->runner->run(
            new ScheduleRunOptions(task: 'lpwork.health.missing-task', force: true),
            new Output($stream, $stream, decorated: false),
        );

        fclose($stream);

        if ($result->failed > 0) {
            return HealthCheckResult::unhealthy($this->name(), 'Scheduler empty probe reported failed tasks.');
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Scheduler runner is resolvable with %d registered task(s).', count($this->schedule->all())));
    }
}
