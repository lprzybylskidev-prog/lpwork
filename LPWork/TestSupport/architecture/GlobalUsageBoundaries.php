<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final readonly class GlobalUsageBoundaries
{
    /**
     * @return list<string>
     */
    public static function superglobals(): array
    {
        return [
            '_COOKIE',
            '_ENV',
            '_FILES',
            '_GET',
            '_GLOBALS',
            '_POST',
            '_REQUEST',
            '_SERVER',
            '_SESSION',
        ];
    }

    /**
     * @return list<string>
     */
    public static function restrictedFunctions(): array
    {
        return [
            'error_reporting',
            'getenv',
            'header',
            'headers_sent',
            'http_response_code',
            'ini_set',
            'session_name',
            'session_set_cookie_params',
            'session_start',
            'session_status',
            'session_write_close',
            'setcookie',
        ];
    }

    public static function canUseSuperglobal(string $path, string $superglobal): bool
    {
        $path = self::normalizePath($path);
        $superglobal = ltrim($superglobal, '$');

        if (self::isTestFile($path)) {
            return true;
        }

        if (str_ends_with($path, '/LPWork/Requests/HttpRequestFactory.php')) {
            return in_array($superglobal, ['_COOKIE', '_FILES', '_GET', '_POST', '_SERVER'], true);
        }

        return str_ends_with($path, '/LPWork/Session/Drivers/PhpSessionDriver.php')
            && $superglobal === '_SESSION';
    }

    public static function canUseFunction(string $path, string $function): bool
    {
        $path = self::normalizePath($path);
        $function = strtolower($function);

        if (self::isTestFile($path)) {
            return true;
        }

        if (str_ends_with($path, '/LPWork/Emitters/HttpEmitter.php')) {
            return in_array($function, ['header', 'http_response_code'], true);
        }

        if (str_ends_with($path, '/LPWork/Session/Drivers/PhpSessionDriver.php')) {
            return in_array($function, [
                'ini_set',
                'session_name',
                'session_set_cookie_params',
                'session_start',
                'session_status',
                'session_write_close',
            ], true);
        }

        if (str_ends_with($path, '/LPWork/Bootstrap/Bootstrap.php')) {
            return in_array($function, ['error_reporting', 'ini_set'], true);
        }

        if (str_ends_with($path, '/LPWork/Console/ProcessEnvironment.php')) {
            return $function === 'getenv';
        }

        return str_ends_with($path, '/LPWork/ErrorHandling/ErrorHandler.php') && $function === 'error_reporting';
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private static function isTestFile(string $path): bool
    {
        return str_contains($path, '/LPWork/Tests/')
            || str_contains($path, '/LPWork/TestSupport/')
            || str_contains($path, '/tests/');
    }
}
