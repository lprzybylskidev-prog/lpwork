<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Config\Exceptions\ConfigAlreadyInitializedException;
use LPWork\Config\Exceptions\ConfigCacheWriteException;
use LPWork\Config\Exceptions\DirectoryNotFoundException;
use LPWork\Config\Exceptions\FileNotFoundException;
use LPWork\Config\Exceptions\FileNotReadableException;
use LPWork\Config\Exceptions\FileReadException;
use LPWork\Config\Exceptions\InvalidFileException;
use LPWork\Config\Exceptions\InvalidKeyException;
use LPWork\Config\Exceptions\InvalidValueException;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Filesystem\Exceptions\DirectoryReadException;
use LPWork\Filesystem\Filesystem;
use LPWork\Shared\Concerns\PreventsSerialization;
use LPWork\Shared\Exceptions\SingletonInstanceException;
use stdClass;
use Throwable;

/**
 * Process-wide configuration repository initialized once during bootstrap and reset explicitly in tests.
 */
final class Config
{
    use PreventsSerialization;

    private static ?self $instance = null;

    /**
     * @var array<string, array<array-key, mixed>>
     */
    private array $configs = [];

    private function __construct() {}

    private function __clone() {}

    /**
     * @param array<array-key, mixed> $config
     */
    private static function fromLoadedConfig(array $config, string $path): self
    {
        $instance = new self();

        foreach ($config as $name => $values) {
            if (!is_string($name) || !is_array($values)) {
                throw new InvalidFileException($path);
            }
        }

        $instance->configs = $config;

        return $instance;
    }

    /**
         * Loads all PHP configuration files from a directory and initializes the global configuration repository.
         */
    public static function init(string $dir): void
    {
        if (self::$instance !== null) {
            throw new ConfigAlreadyInitializedException();
        }

        self::initFiles(self::filesFromDirectory($dir));
    }

    /**
     * Loads specific PHP configuration files and initializes the global configuration repository.
     *
     * @param list<string> $files Absolute or project-resolved PHP files that return configuration arrays.
     */
    public static function initFiles(array $files): void
    {
        self::initSource(new ConfigSourceFiles($files));
    }

    /**
     * Loads configuration from definition objects, preserving their declared keys.
     *
     * @param list<ConfigDefinition> $definitions Config definitions contributed by application and module providers.
     */
    public static function initDefinitions(array $definitions): void
    {
        self::initSource(new ConfigSourceDefinitions($definitions));
    }

    /**
     * Loads configuration from an explicit source boundary.
     */
    public static function initSource(ConfigSource $source): void
    {
        if (self::$instance !== null) {
            throw new ConfigAlreadyInitializedException();
        }

        $instance = new self();
        $instance->configs = $source->load();

        self::$instance = $instance;
    }

    /**
     * Loads configuration from a compiled PHP cache file.
     */
    public static function initCached(string $path): void
    {
        if (self::$instance !== null) {
            throw new ConfigAlreadyInitializedException();
        }

        $filesystem = new Filesystem();

        if (!$filesystem->exists($path)) {
            throw new FileNotFoundException($path);
        }

        if (!$filesystem->isReadable($path)) {
            throw new FileNotReadableException($path);
        }

        $config = include $path;

        if (!is_array($config)) {
            throw new InvalidFileException($path);
        }

        self::$instance = self::fromLoadedConfig($config, $path);
    }

    /**
     * Clears the initialized repository so a new bootstrap or test can initialize configuration again.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Reports whether a dot-notated configuration key exists.
     */
    public static function has(string $key): bool
    {
        $missing = new stdClass();

        return self::optionalValue($key, $missing) !== $missing;
    }

    /**
     * Returns a configuration value by dot-notated key or the provided default when the key is missing.
     */
    public static function get(string $key, mixed ...$default): mixed
    {
        try {
            return self::requiredValue($key);
        } catch (MissingVariableException $exception) {
            if ($default !== []) {
                return $default[0];
            }

            throw $exception;
        }
    }

    /**
     * Returns all loaded top-level configuration arrays.
     *
     * @return array<string, array<array-key, mixed>>
     */
    public static function all(): array
    {
        return self::instance()->configs;
    }

    /**
     * Writes the currently loaded configuration into a compiled PHP cache file.
     */
    public static function writeCache(string $path, ?Filesystem $filesystem = null): void
    {
        $instance = self::instance();

        $content = "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($instance->configs, true) . ";\n";

        try {
            ($filesystem ?? new Filesystem())->write($path, $content);
        } catch (Throwable) {
            throw new ConfigCacheWriteException($path);
        }
    }

    /**
     * Returns a required configuration value as a non-null string.
     */
    public static function getString(string $key): string
    {
        $value = self::requiredValue($key);

        if (!is_string($value)) {
            throw new InvalidValueException($key, 'string');
        }

        return $value;
    }

    /**
     * Returns a required configuration value parsed as an integer.
     */
    public static function getInt(string $key): int
    {
        $value = self::requiredValue($key);

        if (!is_string($value) && !is_int($value)) {
            throw new InvalidValueException($key, 'int');
        }

        $value = (string) $value;

        if (preg_match('/^[+-]?\d+$/', $value) !== 1) {
            throw new InvalidValueException($key, 'int');
        }

        return (int) $value;
    }

    /**
     * Returns a required configuration value parsed as a float.
     */
    public static function getFloat(string $key): float
    {
        $value = self::requiredValue($key);

        if (!is_numeric($value)) {
            throw new InvalidValueException($key, 'float');
        }

        return (float) $value;
    }

    /**
     * Returns a required configuration value parsed through PHP boolean semantics.
     */
    public static function getBool(string $key): bool
    {
        $value = self::requiredValue($key);

        if (is_bool($value)) {
            return $value;
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            throw new InvalidValueException($key, 'bool');
        }

        return $bool;
    }

    /**
     * Returns a required configuration value as an array.
     *
     * @return array<array-key, mixed>
     */
    public static function getArray(string $key): array
    {
        $value = self::requiredValue($key);

        if (!is_array($value)) {
            throw new InvalidValueException($key, 'array');
        }

        return $value;
    }

    private static function instance(): self
    {
        if (self::$instance === null) {
            throw new SingletonInstanceException('Config has not been initialized.');
        }

        return self::$instance;
    }

    /**
     * @return list<string>
     */
    private static function filesFromDirectory(string $dir): array
    {
        $filesystem = new Filesystem();

        if (!$filesystem->isDirectory($dir)) {
            throw new DirectoryNotFoundException($dir);
        }

        try {
            return $filesystem->files($dir . '/*.php');
        } catch (DirectoryReadException) {
            throw new FileReadException();
        }
    }

    private static function requiredValue(string $key): mixed
    {
        $missing = new stdClass();
        $value = self::optionalValue($key, $missing);

        if ($value === $missing) {
            throw new MissingVariableException($key);
        }

        return $value;
    }

    private static function optionalValue(string $key, object $missing): mixed
    {
        $instance = self::instance();

        foreach (self::lookupKeys($key) as $lookupKey) {
            $value = $instance->valueForParsedKey(self::parseKey($lookupKey), $missing);

            if ($value !== $missing) {
                return $value;
            }
        }

        return $missing;
    }

    /**
     * @param list<string> $parsedKey
     */
    private function valueForParsedKey(array $parsedKey, object $missing): mixed
    {
        $value = $this->configs;

        foreach ($parsedKey as $parsedKeyEntry) {
            if (!is_array($value) || !array_key_exists($parsedKeyEntry, $value)) {
                return $missing;
            }

            $value = $value[$parsedKeyEntry];
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private static function lookupKeys(string $key): array
    {
        if (!str_contains($key, '::')) {
            return [$key];
        }

        [$namespace, $nestedKey] = explode('::', $key, 2);

        if ($namespace === '' || $nestedKey === '') {
            throw new InvalidKeyException($key);
        }

        return [
            $namespace . '.' . $nestedKey,
            $nestedKey,
        ];
    }

    /**
     * @return list<string>
     */
    private static function parseKey(string $key): array
    {
        if (self::validateKey($key) === false) {
            throw new InvalidKeyException($key);
        }

        return explode(".", $key);
    }

    private static function validateKey(string $key): bool
    {
        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)*$/', $key) === 1;
    }
}
