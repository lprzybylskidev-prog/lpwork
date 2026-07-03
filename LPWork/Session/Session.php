<?php

declare(strict_types=1);

namespace LPWork\Session;

use LPWork\Http\FormErrors;
use LPWork\Http\OldInput;

/**
 * Holds mutable request session data, flash data, old input, and lifecycle flags.
 */
final class Session
{
    private const string FLASH_KEY = '_flash';

    private FlashData $flash;

    private bool $regenerate = false;

    private bool $invalidate = false;

    /**
     * Creates an in-memory session state object from persisted session data.
     *
     * @param array<string, mixed> $data Raw session payload loaded by the active session driver.
     */
    public function __construct(
        private array $data = [],
    ) {
        $this->flash = FlashData::fromArray($this->data[self::FLASH_KEY] ?? []);
        $this->syncFlashData();
    }

    /**
     * Recreates a session state object from a persisted payload.
     *
     * @param array<string, mixed> $data Raw session payload loaded by the active session driver.
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Returns all current session data, including the framework flash metadata key.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Reports whether a top-level session key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Returns a top-level session value or the supplied default when it is missing.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Stores or replaces a top-level session value.
     */
    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Removes a top-level session value and any matching flash marker.
     */
    public function forget(string $key): void
    {
        unset($this->data[$key]);
        $this->flash->forget($key);
        $this->syncFlashData();
    }

    /**
     * Returns a session value and removes it from the session.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);

        $this->forget($key);

        return $value;
    }

    /**
     * Clears all session data and resets flash metadata.
     */
    public function flush(): void
    {
        $this->data = [];
        $this->flash = FlashData::empty();
        $this->syncFlashData();
    }

    /**
     * Marks the session ID for regeneration after the response is handled.
     */
    public function regenerate(): void
    {
        $this->regenerate = true;
    }

    /**
     * Clears the session and marks the backing driver session ID for invalidation.
     */
    public function invalidate(): void
    {
        $this->flush();
        $this->invalidate = true;
        $this->regenerate();
    }

    /**
     * Stores a value that should be available for the next request only.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $this->flash->flash($key);
        $this->syncFlashData();
    }

    /**
     * Keeps all current flash values for one additional request.
     */
    public function reflash(): void
    {
        $this->flash->reflash();
        $this->syncFlashData();
    }

    /**
     * Keeps selected flash values for one additional request.
     *
     * @param string|list<string> $keys Session key or keys to keep.
     */
    public function keep(string|array $keys): void
    {
        foreach ((array) $keys as $key) {
            $this->flash->keep($key);
        }

        $this->syncFlashData();
    }

    /**
     * Removes expired flash values and advances new flash values to old.
     */
    public function ageFlashData(): void
    {
        foreach ($this->flash->oldKeys() as $key) {
            unset($this->data[$key]);
        }

        $this->flash->age();
        $this->syncFlashData();
    }

    /**
     * Flashes request input for later retrieval through old input helpers.
     *
     * @param array<string, mixed> $input Input data to flash.
     * @param list<string> $except Top-level keys to exclude from flashed input.
     */
    public function flashInput(array $input, array $except = []): void
    {
        OldInput::flash($this, $input, $except);
    }

    /**
     * Returns a previously flashed old input value.
     */
    public function old(string $key, mixed $default = null): mixed
    {
        return OldInput::get($this, $key, $default);
    }

    /**
     * Flashes validation errors for later retrieval by field name.
     *
     * @param array<string, mixed> $errors Field errors to flash.
     */
    public function flashErrors(array $errors): void
    {
        FormErrors::flash($this, $errors);
    }

    /**
     * Returns a flashed validation error value for a field.
     */
    public function error(string $key, mixed $default = null): mixed
    {
        return FormErrors::get($this, $key, $default);
    }

    /**
     * Reports whether session middleware should regenerate the persisted session ID.
     */
    public function regenerationRequested(): bool
    {
        return $this->regenerate;
    }

    /**
     * Reports whether session middleware should invalidate the persisted session.
     */
    public function invalidationRequested(): bool
    {
        return $this->invalidate;
    }

    /**
     * Clears regeneration and invalidation flags after the driver handles them.
     */
    public function clearLifecycleRequests(): void
    {
        $this->regenerate = false;
        $this->invalidate = false;
    }

    private function syncFlashData(): void
    {
        $this->data[self::FLASH_KEY] = $this->flash->toArray();
    }
}
