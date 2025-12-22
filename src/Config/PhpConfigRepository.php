<?php
declare(strict_types=1);

namespace LPwork\Config;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Config\Exception\ConfigValueInvalidException;
use LPwork\Config\Exception\ConfigValueNotFoundException;

/**
 * In-memory configuration repository backed by PHP config files.
 */
class PhpConfigRepository implements ConfigRepositoryInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private readonly array $configs;

    /**
     * @param array<string, array<string, mixed>> $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        [$namespace, $path] = $this->splitKey($key);

        if (!\array_key_exists($namespace, $this->configs)) {
            if ($default !== null) {
                return $default;
            }

            throw new ConfigValueNotFoundException(
                \sprintf('Config namespace "%s" is missing.', $namespace),
            );
        }

        $value = $this->configs[$namespace];

        if ($path === null) {
            return $value;
        }

        foreach ($path as $segment) {
            if (!\is_array($value) || !\array_key_exists($segment, $value)) {
                if ($default !== null) {
                    return $default;
                }

                throw new ConfigValueNotFoundException(
                    \sprintf('Config key "%s" is missing.', $key),
                );
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Returns a string configuration value.
     *
     * @param string      $key
     * @param string|null $default
     *
     * @return string
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);

        if (\is_string($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value) || \is_bool($value)) {
            return (string) $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        throw new ConfigValueInvalidException(
            \sprintf('Config key "%s" is not string-convertible.', $key),
        );
    }

    /**
     * Returns an integer configuration value.
     *
     * @param string   $key
     * @param int|null $default
     *
     * @return int
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);

        if (\is_int($value)) {
            return $value;
        }

        if (\is_numeric($value)) {
            return (int) $value;
        }

        throw new ConfigValueInvalidException(
            \sprintf('Config key "%s" is not integer-convertible.', $key),
        );
    }

    /**
     * Returns a float configuration value.
     *
     * @param string     $key
     * @param float|null $default
     *
     * @return float
     */
    public function getFloat(string $key, ?float $default = null): float
    {
        $value = $this->get($key, $default);

        if (\is_float($value)) {
            return $value;
        }

        if (\is_numeric($value)) {
            return (float) $value;
        }

        throw new ConfigValueInvalidException(
            \sprintf('Config key "%s" is not float-convertible.', $key),
        );
    }

    /**
     * Returns a boolean configuration value.
     *
     * @param string    $key
     * @param bool|null $default
     *
     * @return bool
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $normalized = \strtolower($value);

            if (\in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (\in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        if (\is_int($value) || \is_float($value)) {
            return $value !== 0.0 && $value !== 0;
        }

        throw new ConfigValueInvalidException(
            \sprintf('Config key "%s" is not boolean-convertible.', $key),
        );
    }

    /**
     * Checks if a config key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            $this->get($key);

            return true;
        } catch (ConfigValueNotFoundException) {
            return false;
        }
    }

    /**
     * @param string $key
     *
     * @return array{0: string, 1: array<int, string>|null}
     */
    private function splitKey(string $key): array
    {
        $parts = \explode('.', $key);
        $namespace = (string) \array_shift($parts);

        return [$namespace, $parts === [] ? null : $parts];
    }
}
