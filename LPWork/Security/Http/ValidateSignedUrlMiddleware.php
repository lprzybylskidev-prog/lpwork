<?php

declare(strict_types=1);

namespace LPWork\Security\Http;

use Closure;
use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Security\SignedUrlValidator;

/**
 * Applies validate signed url middleware middleware behavior.
 */
final readonly class ValidateSignedUrlMiddleware implements Middleware
{
    /**
     * Creates a new ValidateSignedUrlMiddleware instance.
     */
    public function __construct(private SignedUrlValidator $validator) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        if (!$this->validator->valid($request)) {
            throw new ForbiddenException('Invalid or expired signed URL.');
        }

        return $next($request);
    }
}
