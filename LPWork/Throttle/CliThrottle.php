<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\Events\EventDispatcher;
use LPWork\Requests\ConsoleRequest;
use LPWork\Responses\ConsoleResponse;
use LPWork\Throttle\Events\CliCommandThrottled;

/**
 * Represents the cli throttle framework component.
 */
final readonly class CliThrottle
{
    /**
     * Creates a new CliThrottle instance.
     */
    public function __construct(
        private ThrottleConfig $config,
        private ThrottleLimiter $limiter,
        private ?EventDispatcher $events = null,
    ) {}

    /**
     * Performs the response operation.
     */
    public function response(ConsoleRequest $request): ?ConsoleResponse
    {
        $policy = $this->config->policy('cli');
        $result = $this->limiter->attempt($policy, $this->key($request));

        if ($result->allowed()) {
            return null;
        }

        $this->events?->dispatch(new CliCommandThrottled([
            'command' => $request->input()->command(),
            'key' => $this->key($request),
            'attempts' => $result->attempts(),
            'max_attempts' => $result->maxAttempts(),
            'retry_after' => $result->retryAfter(),
        ]));

        return ConsoleResponse::output(
            stderr: sprintf('Too many CLI attempts. Retry after %d seconds.', $result->retryAfter()),
            exitCode: 1,
        );
    }

    private function key(ConsoleRequest $request): string
    {
        return 'cli:' . ($request->input()->command() ?? 'list');
    }
}
