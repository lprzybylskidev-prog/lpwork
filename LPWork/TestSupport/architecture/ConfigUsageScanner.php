<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final readonly class ConfigUsageScanner
{
    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    public static function violations(array $directories): array
    {
        $violations = [];

        foreach (PhpFileFinder::inDirectories($directories) as $path) {
            foreach (self::configStaticCallsIn($path) as $method) {
                if (ConfigUsageBoundaries::canUseConfig($path)) {
                    continue;
                }

                $violations[] = sprintf('%s uses Config::%s()', $path, $method);
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function configStaticCallsIn(string $path): array
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            return [];
        }

        preg_match_all(
            '/(?:\\\\?LPWork\\\\Config\\\\Config|(?<![A-Za-z0-9_\\\\])Config)::([a-zA-Z_][A-Za-z0-9_]*)/',
            $content,
            $matches,
        );

        return $matches[1];
    }
}
