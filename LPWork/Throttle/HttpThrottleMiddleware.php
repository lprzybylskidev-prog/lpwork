<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use Closure;
use LPWork\Events\EventDispatcher;
use LPWork\Http\Exceptions\TooManyRequestsException;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Throttle\Events\HttpRequestThrottled;

/**
 * Applies http throttle middleware middleware behavior.
 */
final readonly class HttpThrottleMiddleware implements Middleware
{
    /**
     * Creates a new HttpThrottleMiddleware instance.
     */
    public function __construct(
        private ThrottleLimiter $limiter,
        private ThrottlePolicy $policy,
        private string $flow,
        private ?EventDispatcher $events = null,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $result = $this->limiter->attempt($this->policy, $this->key($request));

        if (!$result->allowed()) {
            $this->events?->dispatch(new HttpRequestThrottled([
                'flow' => $this->flow,
                'path' => $request->path(),
                'key' => $this->key($request),
                'attempts' => $result->attempts(),
                'max_attempts' => $result->maxAttempts(),
                'retry_after' => $result->retryAfter(),
            ]));

            throw TooManyRequestsException::withRetryAfter((string) $result->retryAfter(), 'Too many requests.');
        }

        $response = $next($request);

        if (!$this->policy->enabled()) {
            return $response;
        }

        return new HttpResponse($response->body(), $response->statusCode(), [
            ...$response->headers(),
            'X-RateLimit-Limit' => (string) $result->maxAttempts(),
            'X-RateLimit-Remaining' => (string) $result->remaining(),
        ]);
    }

    private function key(HttpRequest $request): string
    {
        $client = $request->clientIp() !== '' ? $request->clientIp() : 'anonymous';

        return sprintf('http:%s:%s', $this->flow, $client);
    }
}
