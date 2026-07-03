<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Context;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers middleware debug context provider services with the framework container.
 */
final readonly class MiddlewareDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Middleware' => [
                'Resolved pipeline' => $context->middleware(),
            ],
        ];
    }
}
