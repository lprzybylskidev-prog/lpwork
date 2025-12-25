<?php
declare(strict_types=1);

namespace LPwork\Config\Contract;

/**
 * Provides read-only access to configuration values.
 */
interface ConfigRepositoryInterface
{
    /**
     * Returns a configuration value using dot notation (e.g. "app.name").
     *
     * @param string $key
     * @param mixed  ...$default
     *
     * @return mixed
     */
    public function get(string $key, mixed ...$default): mixed;

    /**
     * Returns a string configuration value.
     *
     * @param string      $key
     * @param string|null $default
     *
     * @return string
     */
    public function getString(string $key, ?string $default = null): string;

    /**
     * Returns an integer configuration value.
     *
     * @param string   $key
     * @param int|null $default
     *
     * @return int
     */
    public function getInt(string $key, ?int $default = null): int;

    /**
     * Returns a float configuration value.
     *
     * @param string     $key
     * @param float|null $default
     *
     * @return float
     */
    public function getFloat(string $key, ?float $default = null): float;

    /**
     * Returns a boolean configuration value.
     *
     * @param string    $key
     * @param bool|null $default
     *
     * @return bool
     */
    public function getBool(string $key, ?bool $default = null): bool;

    /**
     * Checks whether the configuration key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;
}
