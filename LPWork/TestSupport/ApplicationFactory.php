<?php

declare(strict_types=1);

namespace Tests\support;

use LPWork\Foundation\Application;

final class ApplicationFactory
{
    public static function create(?string $basePath = null): Application
    {
        return new Application($basePath ?? \Tests\support\ProjectPaths::root());
    }
}
