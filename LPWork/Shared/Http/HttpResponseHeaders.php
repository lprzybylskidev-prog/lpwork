<?php

declare(strict_types=1);

namespace LPWork\Shared\Http;

/**
 * Represents the http response headers framework component.
 */
final readonly class HttpResponseHeaders
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public array $headers,
    ) {}
}
