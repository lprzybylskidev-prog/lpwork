<?php

declare(strict_types=1);

namespace LPWork\Security\Csrf;

use Closure;
use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Applies csrf middleware middleware behavior.
 */
final readonly class CsrfMiddleware implements Middleware
{
    /**
     * Creates a new CsrfMiddleware instance.
     */
    public function __construct(
        private CsrfConfig $config,
        private CsrfTokenManager $tokens,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        if (!$this->config->enabled()) {
            return $next($request);
        }

        if ($this->isReading($request)) {
            $this->tokens->token($request->session());

            return $next($request);
        }

        $valid = $this->config->usesPerFormTokens()
            ? $this->tokens->validForForm($request->session(), $this->formFromRequest($request), $this->tokenFromRequest($request))
            : $this->tokens->valid($request->session(), $this->tokenFromRequest($request));

        if (!$valid) {
            throw new ForbiddenException('Invalid CSRF token.');
        }

        return $next($request);
    }

    private function isReading(HttpRequest $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function tokenFromRequest(HttpRequest $request): ?string
    {
        $inputToken = $request->inputValue($this->config->inputKey());

        if (is_string($inputToken) && $inputToken !== '') {
            return $inputToken;
        }

        return $request->header($this->config->headerName());
    }

    private function formFromRequest(HttpRequest $request): string
    {
        $form = $request->inputValue($this->config->inputKey() . '_form');

        return is_string($form) && $form !== '' ? $form : $request->path();
    }
}
