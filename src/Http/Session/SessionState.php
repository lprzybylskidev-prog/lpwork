<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

/**
 * Immutable session state value object.
 */
final class SessionState
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var int
     */
    private int $lastActivity;

    /**
     * @param string                $id
     * @param array<string, mixed>  $data
     * @param int                   $lastActivity Unix timestamp in seconds.
     */
    public function __construct(string $id, array $data, int $lastActivity)
    {
        $this->id = $id;
        $this->data = $data;
        $this->lastActivity = $lastActivity;
    }

    /**
     * Returns session identifier.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Returns last activity timestamp.
     *
     * @return int
     */
    public function lastActivity(): int
    {
        return $this->lastActivity;
    }

    /**
     * Returns all stored values.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
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
        return \array_key_exists($key, $this->data);
    }

    /**
     * Returns value for key.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Returns a new state with updated value.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $lastActivity Unix timestamp in seconds.
     *
     * @return self
     */
    public function with(string $key, mixed $value, int $lastActivity): self
    {
        $data = $this->data;
        $data[$key] = $value;

        return new self($this->id, $data, $lastActivity);
    }

    /**
     * Returns a new state without the given key.
     *
     * @param string $key
     * @param int    $lastActivity Unix timestamp in seconds.
     *
     * @return self
     */
    public function without(string $key, int $lastActivity): self
    {
        $data = $this->data;
        unset($data[$key]);

        return new self($this->id, $data, $lastActivity);
    }

    /**
     * Returns a new empty state.
     *
     * @param int $lastActivity Unix timestamp in seconds.
     *
     * @return self
     */
    public function cleared(int $lastActivity): self
    {
        return new self($this->id, [], $lastActivity);
    }

    /**
     * Returns a new state with a regenerated identifier.
     *
     * @param string $newId
     * @param int    $lastActivity Unix timestamp in seconds.
     *
     * @return self
     */
    public function withId(string $newId, int $lastActivity): self
    {
        return new self($newId, $this->data, $lastActivity);
    }
}
