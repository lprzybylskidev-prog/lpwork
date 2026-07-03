<?php

declare(strict_types=1);

namespace LPWork\Requests;

use Stringable;

/**
 * Represents the request data accessor framework component.
 */
final readonly class RequestDataAccessor
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data) {}

    /**
     * Returns value.
     */
    public function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $value = $this->nestedValue($key);

        return $value->found ? $value->value : $default;
    }

    /**
     * Reports whether has.
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->data)) {
            return true;
        }

        return $this->nestedValue($key)->found;
    }

    /**
     * Reports whether filled.
     */
    public function filled(string $key): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        $value = $this->value($key);

        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Reports whether missing.
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Performs the string operation.
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->value($key);

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Performs the integer operation.
     */
    public function integer(string $key, int $default = 0): int
    {
        $value = $this->value($key);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * Performs the float operation.
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->value($key);

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * Performs the boolean operation.
     */
    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->value($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : $default);
        }

        if (!is_string($value)) {
            return $default;
        }

        return match (strtolower($value)) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => $default,
        };
    }

    /**
     * @param array<array-key, mixed> $default
     *
     * @return array<array-key, mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->value($key);

        return is_array($value) ? $value : $default;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $selected = [];

        foreach ($keys as $key) {
            if (!$this->has($key)) {
                continue;
            }

            $this->setNestedValue($selected, $key, $this->value($key));
        }

        return $selected;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $filtered = $this->data;

        foreach ($keys as $key) {
            if (array_key_exists($key, $filtered)) {
                unset($filtered[$key]);

                continue;
            }

            $this->unsetNestedValue($filtered, $key);
        }

        return $filtered;
    }

    private function nestedValue(string $key): RequestDataValue
    {
        $value = $this->data;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return new RequestDataValue(false);
            }

            $value = $value[$segment];
        }

        return new RequestDataValue(true, $value);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setNestedValue(array &$data, string $key, mixed $value): void
    {
        if (array_key_exists($key, $this->data)) {
            $data[$key] = $value;

            return;
        }

        $target = &$data;

        foreach (explode('.', $key) as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function unsetNestedValue(array &$data, string $key): void
    {
        $segments = explode('.', $key);
        $last = array_pop($segments);
        $target = &$data;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                return;
            }

            $target = &$target[$segment];
        }

        unset($target[$last]);
    }
}
