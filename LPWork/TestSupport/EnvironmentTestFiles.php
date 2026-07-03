<?php

declare(strict_types=1);

namespace Tests\support;

use RuntimeException;

final class EnvironmentTestFiles
{
    /**
     * @var list<string>
     */
    private static array $files = [];

    private static ?string $file = null;

    public static function createFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lpwork_env_');

        if ($path === false) {
            throw new RuntimeException('Could not create temporary environment file.');
        }

        file_put_contents($path, $content);

        self::$files[] = $path;

        return $path;
    }

    public static function file(): string
    {
        if (self::$file === null) {
            self::$file = self::createFile('');
        }

        return self::$file;
    }

    public static function resetFile(): void
    {
        self::$file = self::createFile('');
    }

    public static function appendLine(string $line, ?string $path = null): void
    {
        $path ??= self::file();

        self::appendContent($path, $line);
    }

    public static function appendValue(string $key, string|int|float|bool $value, ?string $path = null): void
    {
        $path ??= self::file();

        self::appendContent($path, sprintf('%s=%s', $key, self::formatValue($value)));
    }

    public static function setValue(string $key, string|int|float|bool $value, ?string $path = null): void
    {
        $path ??= self::file();

        self::setContentValue($path, $key, self::formatValue($value));
    }

    public static function removeFiles(): void
    {
        foreach (self::$files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        self::$files = [];
        self::$file = null;
    }

    private static function appendContent(string $path, string $content): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Environment file does not exist: %s', $path));
        }

        $currentContent = file_get_contents($path);

        if ($currentContent === false) {
            throw new RuntimeException(sprintf('Could not read environment file: %s', $path));
        }

        $separator = str_ends_with($currentContent, "\n") || $currentContent === '' ? '' : "\n";

        file_put_contents($path, $separator . $content . "\n", FILE_APPEND);
    }

    private static function setContentValue(string $path, string $key, string $value): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Environment file does not exist: %s', $path));
        }

        $currentContent = file_get_contents($path);

        if ($currentContent === false) {
            throw new RuntimeException(sprintf('Could not read environment file: %s', $path));
        }

        $lines = preg_split('/\r\n|\r|\n/', $currentContent);

        if ($lines === false) {
            throw new RuntimeException(sprintf('Could not split environment file: %s', $path));
        }

        $wasUpdated = false;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', $line) !== 1) {
                continue;
            }

            $lines[$index] = sprintf('%s=%s', $key, $value);
            $wasUpdated = true;

            break;
        }

        $content = implode("\n", $lines);

        if (!$wasUpdated) {
            self::appendContent($path, sprintf('%s=%s', $key, $value));

            return;
        }

        if (!str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        file_put_contents($path, $content);
    }

    private static function formatValue(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === '' || preg_match('/\s|["\']/', $value) === 1) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }

        return $value;
    }
}
