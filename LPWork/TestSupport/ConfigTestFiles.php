<?php

declare(strict_types=1);

namespace Tests\support;

use RuntimeException;

final class ConfigTestFiles
{
    /**
     * @var list<string>
     */
    private static array $directories = [];

    private static ?string $directory = null;

    public static function createDirectory(): string
    {
        $path = sys_get_temp_dir() . '/lpwork_config_' . uniqid('', true);

        if (!mkdir($path)) {
            throw new RuntimeException('Could not create temporary config directory.');
        }

        self::$directories[] = $path;

        return $path;
    }

    public static function directory(): string
    {
        if (self::$directory === null) {
            self::$directory = self::createDirectory();
        }

        return self::$directory;
    }

    public static function resetDirectory(): void
    {
        self::$directory = self::createDirectory();
    }

    public static function createFile(string $fileName, string $content, ?string $directory = null): string
    {
        $directory ??= self::directory();
        $path = $directory . '/' . $fileName;
        $fileDirectory = dirname($path);

        if (!is_dir($fileDirectory) && !mkdir($fileDirectory, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create config directory: %s', $fileDirectory));
        }

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public static function createConfig(string $fileName, array $config = [], ?string $directory = null): string
    {
        $directory ??= self::directory();
        $path = $directory . '/' . $fileName;
        $fileDirectory = dirname($path);

        if (!is_dir($fileDirectory) && !mkdir($fileDirectory, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create config directory: %s', $fileDirectory));
        }

        if (is_file($path)) {
            throw new RuntimeException(sprintf('Config file already exists: %s', $path));
        }

        self::writeConfig($path, $config);

        return $path;
    }

    public static function appendValue(string $fileName, string $key, mixed $value, ?string $directory = null): string
    {
        $directory ??= self::directory();
        $path = $directory . '/' . $fileName;
        $config = self::readConfig($path);

        self::setNestedValue($config, explode('.', $key), $value);
        self::writeConfig($path, $config);

        return $path;
    }

    public static function removeDirectories(): void
    {
        foreach (self::$directories as $directory) {
            self::removeDirectory($directory);
        }
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read config directory: %s', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                self::removeDirectory($path);

                continue;
            }

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function readConfig(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        self::ensureConfigCanBeIncluded($path);

        $config = include $path;

        if (!is_array($config)) {
            throw new RuntimeException(sprintf('Config file does not return an array: %s', $path));
        }

        return $config;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function writeConfig(string $path, array $config): void
    {
        $content = "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($config, true) . ";\n";

        file_put_contents($path, $content);
    }

    private static function ensureConfigCanBeIncluded(string $path): void
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Could not read config file: %s', $path));
        }

        if (preg_match('/\bEnvironment::get[A-Za-z]*\s*\(/', $content) === 1) {
            throw new RuntimeException(
                sprintf(
                    'Cannot append values to config file "%s" because it reads from Environment. Create a dedicated test config file instead.',
                    $path,
                ),
            );
        }
    }

    /**
     * @param array<array-key, mixed> $config
     * @param list<string> $keys
     */
    private static function setNestedValue(array &$config, array $keys, mixed $value): void
    {
        $key = array_shift($keys);

        if ($key === null || $key === '') {
            throw new RuntimeException('Config key cannot be empty.');
        }

        if ($keys === []) {
            $config[$key] = $value;

            return;
        }

        if (!array_key_exists($key, $config) || !is_array($config[$key])) {
            $config[$key] = [];
        }

        self::setNestedValue($config[$key], $keys, $value);
    }
}
