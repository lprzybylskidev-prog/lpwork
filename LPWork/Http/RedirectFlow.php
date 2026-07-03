<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Responses\HttpResponse;
use LPWork\Session\Session;

/**
 * Represents the redirect flow framework component.
 */
final readonly class RedirectFlow
{
    /**
     * Creates a new RedirectFlow instance.
     */
    public function __construct(
        private HttpResponse $response,
        private Session $session,
    ) {}

    /**
     * Returns a copy with with applied.
     */
    public function with(string $key, mixed $value): self
    {
        Redirect::with($this->response, $this->session, $key, $value);

        return $this;
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string> $except
     */
    public function withInput(array $input, array $except = []): self
    {
        Redirect::withInput($this->response, $this->session, $input, $except);

        return $this;
    }

    /**
     * @param array<string, mixed> $errors
     */
    public function withErrors(array $errors): self
    {
        Redirect::withErrors($this->response, $this->session, $errors);

        return $this;
    }

    /**
     * Performs the response operation.
     */
    public function response(): HttpResponse
    {
        return $this->response;
    }
}
