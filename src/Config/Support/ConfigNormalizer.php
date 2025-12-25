<?php
declare(strict_types=1);

namespace LPwork\Config\Support;

use LPwork\Config\Exception\InvalidConfigurationException;

/**
 * Shared helpers for normalizing and validating configuration values.
 */
trait ConfigNormalizer
{
    /**
     * @param mixed       $value
     * @param string      $path
     * @param bool|null   $default
     * @param bool        $required
     *
     * @return bool
     */
    protected function boolVal(
        mixed $value,
        string $path,
        ?bool $default = null,
        bool $required = false,
    ): bool {
        if ($value === null) {
            if ($required && $default === null) {
                throw new InvalidConfigurationException(\sprintf('Missing boolean "%s".', $path));
            }

            return (bool) ($default ?? false);
        }

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return $value !== 0;
        }

        if (\is_string($value)) {
            $normalized = \strtolower(\trim($value));
            if (\in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (\in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        throw new InvalidConfigurationException(\sprintf('Invalid boolean for "%s".', $path));
    }

    /**
     * @param mixed    $value
     * @param string   $path
     * @param int|null $default
     * @param int|null $min
     *
     * @return int
     */
    protected function intVal(
        mixed $value,
        string $path,
        ?int $default = null,
        ?int $min = null,
    ): int {
        if ($value === null) {
            if ($default === null) {
                throw new InvalidConfigurationException(\sprintf('Missing integer "%s".', $path));
            }

            $value = $default;
        }

        if (!\is_int($value) && !\is_numeric($value)) {
            throw new InvalidConfigurationException(\sprintf('Invalid integer for "%s".', $path));
        }

        $int = (int) $value;

        if ($min !== null && $int < $min) {
            throw new InvalidConfigurationException(
                \sprintf('Value for "%s" must be >= %d.', $path, $min),
            );
        }

        return $int;
    }

    /**
     * @param mixed       $value
     * @param string      $path
     * @param string|null $default
     * @param bool        $allowEmpty
     *
     * @return string
     */
    protected function stringVal(
        mixed $value,
        string $path,
        ?string $default = null,
        bool $allowEmpty = true,
    ): string {
        if ($value === null) {
            if ($default === null) {
                throw new InvalidConfigurationException(\sprintf('Missing string "%s".', $path));
            }

            $value = $default;
        }

        if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
            throw new InvalidConfigurationException(\sprintf('Invalid string for "%s".', $path));
        }

        $string = \trim((string) $value);

        if (!$allowEmpty && $string === '') {
            throw new InvalidConfigurationException(
                \sprintf('Value for "%s" must be a non-empty string.', $path),
            );
        }

        return $string;
    }

    /**
     * @param mixed            $value
     * @param string           $path
     * @param array<int,mixed> $allowed
     * @param mixed|null       $default
     *
     * @return mixed
     */
    protected function enumVal(
        mixed $value,
        string $path,
        array $allowed,
        mixed $default = null,
    ): mixed {
        if ($value === null) {
            $value = $default;
        }

        if (!\in_array($value, $allowed, true)) {
            throw new InvalidConfigurationException(
                \sprintf(
                    'Invalid value for "%s": "%s". Allowed: %s',
                    $path,
                    \is_scalar($value) ? (string) $value : \gettype($value),
                    \implode(', ', \array_map(static fn($v): string => (string) $v, $allowed)),
                ),
            );
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param string $path
     * @param bool $allowEmptyEntries
     * @param bool $splitComma
     *
     * @return array<int, string>
     */
    protected function stringList(
        mixed $value,
        string $path,
        bool $allowEmptyEntries = false,
        bool $splitComma = true,
    ): array {
        $items = [];

        foreach ((array) $value as $entry) {
            if ($splitComma && \is_string($entry) && \str_contains($entry, ',')) {
                foreach (\explode(',', $entry) as $part) {
                    $part = \trim($part);
                    if ($part !== '' || $allowEmptyEntries) {
                        $items[] = $part;
                    }
                }
                continue;
            }

            $normalized = \trim((string) $entry);
            if ($normalized === '' && !$allowEmptyEntries) {
                continue;
            }

            $items[] = $normalized;
        }

        if (!$allowEmptyEntries) {
            $items = \array_values(\array_filter($items, static fn(string $i): bool => $i !== ''));
        }

        return $items;
    }
}
