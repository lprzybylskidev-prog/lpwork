<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_slice;
use function count;
use function str_starts_with;

/**
 * Represents the console argv normalizer framework component.
 */
final readonly class ConsoleArgvNormalizer
{
    /**
     * @param array<int, string> $argv
     *
     * @return array<int, string>
     */
    public function normalize(array $argv): array
    {
        if (count($argv) < 2) {
            return $argv;
        }

        $script = $argv[0] ?? '';
        $globalOptions = [];
        $tokens = array_slice($argv, 1);

        for ($index = 0; $index < count($tokens); $index++) {
            $token = $tokens[$index];

            if (str_starts_with($token, '--module=')) {
                $globalOptions[] = $token;

                continue;
            }

            if ($token === '--module') {
                $globalOptions[] = $token;

                if (isset($tokens[$index + 1])) {
                    $globalOptions[] = $tokens[$index + 1];
                    $index++;
                }

                continue;
            }

            return [$script, $token, ...array_slice($tokens, $index + 1), ...$globalOptions];
        }

        return [$script, '', ...$globalOptions];
    }
}
