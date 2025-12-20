<?php
declare(strict_types=1);

namespace LPwork\Runtime;

/**
 * Enum describing the runtime environment of the framework.
 */
enum RuntimeType: string
{
    /**
     * Indicates execution in CLI context.
     */
    case Cli = 'cli';

    /**
     * Indicates execution in HTTP context.
     */
    case Http = 'http';
}
