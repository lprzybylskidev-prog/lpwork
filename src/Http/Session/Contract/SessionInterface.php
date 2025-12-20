<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Contract;

/**
 * Immutable session facade exposed to application code.
 */
interface SessionInterface
{
    /**
     * Returns session identifier.
     *
     * @return string
     */
    public function id(): string;

    /**
     * Checks if key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Returns value for given key.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Returns all stored values.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Returns new session with given key set.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function with(string $key, mixed $value): self;

    /**
     * Returns new session without given key.
     *
     * @param string $key
     *
     * @return self
     */
    public function without(string $key): self;

    /**
     * Returns new session cleared of all data.
     *
     * @return self
     */
    public function clear(): self;

    /**
     * Returns new session with regenerated identifier.
     *
     * @return self
     */
    public function regenerateId(): self;
}
