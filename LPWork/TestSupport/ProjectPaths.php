<?php

declare(strict_types=1);

namespace Tests\support;

final readonly class ProjectPaths
{
    public static function root(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);

        if ($path === '') {
            return $root;
        }

        return $root . '/' . ltrim($path, '/');
    }
}
