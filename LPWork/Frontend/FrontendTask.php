<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Enumerates the supported frontend task values.
 */
enum FrontendTask
{
    case Install;
    case Dev;
    case Build;
    case Clean;
    case Format;
    case Check;
    case Test;

    /**
     * Performs the script operation.
     */
    public function script(): ?string
    {
        return match ($this) {
            self::Install, self::Clean => null,
            self::Dev => 'frontend:dev',
            self::Build => 'frontend:build',
            self::Format => 'frontend:format',
            self::Check => 'frontend:check',
            self::Test => 'frontend:test',
        };
    }
}
