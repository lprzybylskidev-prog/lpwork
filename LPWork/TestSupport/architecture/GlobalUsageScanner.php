<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final class GlobalUsageScanner
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
            $violations = [
                ...$violations,
                ...self::superglobalViolations($path),
                ...self::functionViolations($path),
            ];
        }

        sort($violations);

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function superglobalViolations(string $path): array
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            return [];
        }

        $violations = [];

        foreach (GlobalUsageBoundaries::superglobals() as $superglobal) {
            $variable = '$' . $superglobal;

            if (!str_contains($content, $variable)) {
                continue;
            }

            if (GlobalUsageBoundaries::canUseSuperglobal($path, $superglobal)) {
                continue;
            }

            $violations[] = sprintf('%s uses %s', $path, $variable);
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function functionViolations(string $path): array
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            return [];
        }

        $tokens = token_get_all($content);
        $violations = [];

        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_STRING) {
                continue;
            }

            $function = strtolower($token[1]);

            if (!in_array($function, GlobalUsageBoundaries::restrictedFunctions(), true)) {
                continue;
            }

            if (!self::isFunctionCall($tokens, $index)) {
                continue;
            }

            if (GlobalUsageBoundaries::canUseFunction($path, $function)) {
                continue;
            }

            $violations[] = sprintf('%s calls %s()', $path, $function);
        }

        return array_values(array_unique($violations));
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function isFunctionCall(array $tokens, int $index): bool
    {
        $next = self::nextMeaningfulToken($tokens, $index);

        if ($next !== '(') {
            return false;
        }

        $previous = self::previousMeaningfulToken($tokens, $index);

        if (is_array($previous)) {
            return !in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true);
        }

        return true;
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     *
     * @return array{0: int, 1: string, 2: int}|string|null
     */
    private static function nextMeaningfulToken(array $tokens, int $index): array|string|null
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (self::isIgnorableToken($tokens[$i])) {
                continue;
            }

            return $tokens[$i];
        }

        return null;
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     *
     * @return array{0: int, 1: string, 2: int}|string|null
     */
    private static function previousMeaningfulToken(array $tokens, int $index): array|string|null
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (self::isIgnorableToken($tokens[$i])) {
                continue;
            }

            return $tokens[$i];
        }

        return null;
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private static function isIgnorableToken(array|string $token): bool
    {
        return is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

}
