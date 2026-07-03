<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Requests\HttpRequest;

/**
 * Represents the signed url validator framework component.
 */
final readonly class SignedUrlValidator
{
    /**
     * Creates a new SignedUrlValidator instance.
     */
    public function __construct(private SignedUrl $signedUrl) {}

    /**
     * Reports whether valid.
     */
    public function valid(HttpRequest $request): bool
    {
        return $this->signedUrl->verify($request->fullUrl())
            || $this->signedUrl->verify($request->uri());
    }
}
