<?php

declare(strict_types=1);

namespace LPWork\Validation;

use LPWork\Requests\HttpRequest;
use LPWork\Requests\RequestDataAccessor;
use LPWork\Session\Session;
use LPWork\Validation\Exceptions\FormRequestAlreadyInitializedException;
use LPWork\Validation\Exceptions\FormRequestNotInitializedException;

/**
 * Represents the form request framework component.
 */
abstract class FormRequest
{
    private ?HttpRequest $request = null;

    /**
     * @var array<string, mixed>
     */
    private array $validated = [];

    /**
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * Performs the initialize request operation.
     */
    public function initializeRequest(HttpRequest $request): void
    {
        if ($this->request !== null) {
            throw new FormRequestAlreadyInitializedException();
        }

        $this->request = $request;
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function initializeValidated(array $validated): void
    {
        $this->validated = $validated;
    }

    /**
     * Performs the request operation.
     */
    public function request(): HttpRequest
    {
        return $this->request ?? throw new FormRequestNotInitializedException();
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Reports whether validated value.
     */
    public function validatedValue(string $key, mixed $default = null): mixed
    {
        return $this->validatedAccessor()->value($key, $default);
    }

    /**
     * Performs the string operation.
     */
    public function string(string $key, string $default = ''): string
    {
        return $this->validatedAccessor()->string($key, $default);
    }

    /**
     * Performs the integer operation.
     */
    public function integer(string $key, int $default = 0): int
    {
        return $this->validatedAccessor()->integer($key, $default);
    }

    /**
     * Returns method.
     */
    public function method(): string
    {
        return $this->request()->method();
    }

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->request()->path();
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->request()->input();
    }

    /**
     * Performs the header operation.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        return $this->request()->header($name, $default);
    }

    /**
     * Returns session.
     */
    public function session(): Session
    {
        return $this->request()->session();
    }

    private function validatedAccessor(): RequestDataAccessor
    {
        return new RequestDataAccessor($this->validated);
    }
}
