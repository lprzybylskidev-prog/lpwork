<?php

declare(strict_types=1);

namespace LPWork\Security\Events;

use LPWork\Requests\HttpRequest;

/**
 * Represents the http security denied framework component.
 */
final readonly class HttpSecurityDenied
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $reason,
        public string $message,
        public array $context,
        public ?HttpRequest $request = null,
    ) {}
}
