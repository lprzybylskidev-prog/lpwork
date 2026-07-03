<?php

declare(strict_types=1);

namespace LPWork\Console\ProjectTasks;

/**
 * Enumerates the supported project task values.
 */
enum ProjectTask
{
    case Format;
    case Check;
    case Test;
    case Coverage;
    case TestLpwork;
}
