<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Enumerates the supported scheduled task type values.
 */
enum ScheduledTaskType: string
{
    case Command = 'command';
    case Job = 'job';
}
