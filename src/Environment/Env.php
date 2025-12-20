<?php
declare(strict_types=1);

namespace LPwork\Environment;

use LPwork\Environment\Exception\EnvValueInvalidException;
use LPwork\Environment\Exception\EnvValueNotFoundException;

/**
 * Immutable wrapper around environment variables with typed accessors.
 */
final class Env
{
    /**
     * @var array<string, string>
     */
    private readonly array $values;

    /**
     * @param array<string, string> $values
     */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Creates an Env instance from a key-value map.
     *
     * @param array<string, string> $values
     *
     * @return self
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * Returns raw string value.
     *
     * @param string      $key
     * @param string|null $default
     *
     * @return string
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->getRaw($key, $default);

        return $value;
    }

    /**
     * Returns integer value.
     *
     * @param string   $key
     * @param int|null $default
     *
     * @return int
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->getRaw(
            $key,
            $default !== null ? (string) $default : null,
        );

        if (!\is_numeric($value)) {
            throw new EnvValueInvalidException(
                \sprintf('Environment key "%s" is not numeric.', $key),
            );
        }

        return (int) $value;
    }

    /**
     * Returns float value.
     *
     * @param string     $key
     * @param float|null $default
     *
     * @return float
     */
    public function getFloat(string $key, ?float $default = null): float
    {
        $value = $this->getRaw(
            $key,
            $default !== null ? (string) $default : null,
        );

        if (!\is_numeric($value)) {
            throw new EnvValueInvalidException(
                \sprintf('Environment key "%s" is not numeric.', $key),
            );
        }

        return (float) $value;
    }

    /**
     * Returns boolean value parsed from common string representations.
     *
     * @param string    $key
     * @param bool|null $default
     *
     * @return bool
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->getRaw(
            $key,
            $default !== null ? ($default ? "true" : "false") : null,
        );
        $normalized = \strtolower($value);

        if (\in_array($normalized, ["1", "true", "yes", "on"], true)) {
            return true;
        }

        if (\in_array($normalized, ["0", "false", "no", "off", ""], true)) {
            return false;
        }

        throw new EnvValueInvalidException(
            \sprintf('Environment key "%s" is not boolean-convertible.', $key),
        );
    }

    /**
     * Checks if key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    /**
     * @param string      $key
     * @param string|null $default
     *
     * @return string
     */
    private function getRaw(string $key, ?string $default = null): string
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }

        if ($default !== null) {
            return $default;
        }

        throw new EnvValueNotFoundException(
            \sprintf('Environment key "%s" is missing.', $key),
        );
    }
}
