<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final class EnvironmentUsageScanner
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
            foreach (self::environmentStaticCallsIn($path) as $method) {
                if (EnvironmentUsageBoundaries::canUseEnvironment($path, $method)) {
                    continue;
                }

                $violations[] = sprintf('%s uses Environment::%s()', $path, $method);
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function environmentStaticCallsIn(string $path): array
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            return [];
        }

        preg_match_all(
            '/(?:\\\\?LPWork\\\\Environment\\\\Environment|(?<![A-Za-z0-9_\\\\])Environment)::([a-zA-Z_][a-zA-Z0-9_]*)/',
            $content,
            $matches,
        );

        return $matches[1];
    }

}
