<?php

declare(strict_types=1);

namespace LPWork\Shared\Http;

/**
 * Represents the http response framework component.
 */
final readonly class HttpResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {}

    /**
     * Performs the successful operation.
     */
    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
