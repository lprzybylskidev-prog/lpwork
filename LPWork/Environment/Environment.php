<?php

declare(strict_types=1);

namespace LPWork\Environment;

use LPWork\Environment\Exceptions\EnvironmentAlreadyInitializedException;
use LPWork\Environment\Exceptions\FileNotFoundException;
use LPWork\Environment\Exceptions\FileNotReadableException;
use LPWork\Environment\Exceptions\FileReadException;
use LPWork\Environment\Exceptions\InvalidValueException;
use LPWork\Environment\Exceptions\MissingVariableException;
use LPWork\Filesystem\Exceptions\FileReadException as FilesystemFileReadException;
use LPWork\Filesystem\Filesystem;
use LPWork\Shared\Concerns\PreventsSerialization;
use LPWork\Shared\Exceptions\SingletonInstanceException;

/**
 * Represents the environment framework component.
 */
final class Environment
{
    use PreventsSerialization;

    private static ?self $instance = null;

    private string $rawContent = '';

    /**
     * @var array<string, string>
     */
    private array $parsedContent = [];

    private function __construct(
        private readonly string $path,
        private readonly EnvironmentParser $parser = new EnvironmentParser(),
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->load();
    }

    private function __clone() {}

    /**
     * Performs the init operation.
     */
    public static function init(string $path): void
    {
        if (self::$instance !== null) {
            throw new EnvironmentAlreadyInitializedException();
        }

        self::$instance = new self($path);
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Reports whether has.
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::instance()->parsedContent);
    }

    /**
     * Returns the requested value from this component.
     */
    public static function get(string $key, mixed ...$default): string
    {
        $instance = self::instance();

        if (!array_key_exists($key, $instance->parsedContent)) {
            if ($default !== []) {
                $value = $default[0];

                if (!is_string($value)) {
                    throw new InvalidValueException($key, 'string');
                }

                return $value;
            }

            throw new MissingVariableException($key);
        }

        return $instance->parsedContent[$key];
    }

    /**
     * Returns get string.
     */
    public static function getString(string $key): string
    {
        return self::get($key);
    }

    /**
     * Returns get int.
     */
    public static function getInt(string $key): int
    {
        $value = self::value($key);

        if (preg_match('/^[+-]?\d+$/', $value) !== 1) {
            throw new InvalidValueException($key, 'int');
        }

        return (int) $value;
    }

    /**
     * Returns get float.
     */
    public static function getFloat(string $key): float
    {
        $value = self::value($key);

        if (!is_numeric($value)) {
            throw new InvalidValueException($key, 'float');
        }

        return (float) $value;
    }

    /**
     * Returns get bool.
     */
    public static function getBool(string $key): bool
    {
        $value = strtolower(self::value($key));

        $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            throw new InvalidValueException($key, 'bool');
        }

        return $bool;
    }

    private static function instance(): self
    {
        if (self::$instance === null) {
            throw new SingletonInstanceException('Environment has not been initialized.');
        }

        return self::$instance;
    }

    private static function value(string $key): string
    {
        $instance = self::instance();

        if (!array_key_exists($key, $instance->parsedContent)) {
            throw new MissingVariableException($key);
        }

        return $instance->parsedContent[$key];
    }

    private function load(): void
    {
        if (!$this->filesystem->exists($this->path)) {
            throw new FileNotFoundException($this->path);
        }

        if (!$this->filesystem->isReadable($this->path)) {
            throw new FileNotReadableException($this->path);
        }

        try {
            $rawContent = $this->filesystem->read($this->path);
        } catch (FilesystemFileReadException) {
            throw new FileReadException($this->path);
        }

        $this->rawContent = $rawContent;
        $this->parsedContent = $this->parser->parse($this->rawContent, $this->path);
    }
}
