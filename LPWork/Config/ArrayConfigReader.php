<?php

declare(strict_types=1);

namespace LPWork\Config;

use Closure;
use Throwable;

/**
 * Reads and validates structured configuration arrays while preserving domain-specific exception types.
 */
final readonly class ArrayConfigReader
{
    /**
     * @param array<array-key, mixed> $config
     * @param Closure(string): Throwable $missingException
     * @param Closure(string): Throwable $invalidException
     */
    public function __construct(
        private array $config,
        private Closure $missingException,
        private Closure $invalidException,
    ) {}

    /**
     * Reads a required non-empty string value.
     */
    public function string(string $name, ?string $key = null): string
    {
        $key ??= $name;
        $value = $this->value($name, $key);

        if (!is_string($value) || $value === '') {
            throw ($this->invalidException)($key);
        }

        return $value;
    }

    /**
     * Reads an optional string value, optionally allowing an empty string as a deliberate configured value.
     */
    public function optionalString(string $name, string $key, bool $allowEmpty = false): ?string
    {
        if (!array_key_exists($name, $this->config) || $this->config[$name] === null) {
            return null;
        }

        $value = $this->config[$name];

        if (!is_string($value) || (!$allowEmpty && $value === '')) {
            throw ($this->invalidException)($key);
        }

        return $value;
    }

    /**
     * Reads a list of string values.
     *
     * @return list<string>
     */
    public function stringList(string $name, ?string $key = null, bool $allowEmpty = false): array
    {
        $key ??= $name;
        $values = $this->array($name, $key);

        if (!array_is_list($values)) {
            throw ($this->invalidException)($key);
        }

        $strings = [];

        foreach ($values as $value) {
            if (!is_string($value) || (!$allowEmpty && $value === '')) {
                throw ($this->invalidException)($key);
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * Reads an associative string map with non-empty string keys.
     *
     * @return array<string, string>
     */
    public function stringMap(string $name, ?string $key = null, bool $allowEmpty = false): array
    {
        $key ??= $name;
        $values = $this->array($name, $key);
        $strings = [];

        foreach ($values as $mapKey => $value) {
            if (!is_string($mapKey) || $mapKey === '' || !is_string($value) || (!$allowEmpty && $value === '')) {
                throw ($this->invalidException)($key);
            }

            $strings[$mapKey] = $value;
        }

        return $strings;
    }

    /**
     * Reads a required non-negative integer value.
     */
    public function int(string $name, string $key): int
    {
        $value = $this->value($name, $key);

        if (!is_int($value) || $value < 0) {
            throw ($this->invalidException)($key);
        }

        return $value;
    }

    /**
     * Reads a required boolean value.
     */
    public function bool(string $name, string $key): bool
    {
        $value = $this->value($name, $key);

        if (!is_bool($value)) {
            throw ($this->invalidException)($key);
        }

        return $value;
    }

    /**
     * Reads an optional boolean value.
     */
    public function optionalBool(string $name, string $key): ?bool
    {
        if (!array_key_exists($name, $this->config) || $this->config[$name] === null) {
            return null;
        }

        $value = $this->config[$name];

        if (!is_bool($value)) {
            throw ($this->invalidException)($key);
        }

        return $value;
    }

    /**
     * Reads a required array value.
     *
     * @return array<array-key, mixed>
     */
    public function array(string $name, ?string $key = null): array
    {
        $key ??= $name;
        $value = $this->value($name, $key);

        if (!is_array($value)) {
            throw ($this->invalidException)($key);
        }

        return $value;
    }

    /**
     * Reads an associative map whose values are configuration arrays.
     *
     * @return array<string, array<array-key, mixed>>
     */
    public function arrayMap(string $name, ?string $key = null): array
    {
        $key ??= $name;
        $values = $this->array($name, $key);
        $map = [];

        foreach ($values as $mapKey => $value) {
            if (!is_string($mapKey) || $mapKey === '' || !is_array($value)) {
                throw ($this->invalidException)($key);
            }

            $map[$mapKey] = $value;
        }

        return $map;
    }

    private function value(string $name, string $key): mixed
    {
        if (!array_key_exists($name, $this->config)) {
            throw ($this->missingException)($key);
        }

        return $this->config[$name];
    }
}
