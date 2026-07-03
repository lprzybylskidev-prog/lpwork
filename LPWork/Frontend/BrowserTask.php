<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Enumerates the supported browser task values.
 */
enum BrowserTask
{
    case Install;
    case Test;
    case Ui;

    /**
     * Performs the script operation.
     */
    public function script(): string
    {
        return match ($this) {
            self::Install => 'browser:install',
            self::Test => 'browser:test',
            self::Ui => 'browser:test:ui',
        };
    }
}
