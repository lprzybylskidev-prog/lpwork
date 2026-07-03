<?php

declare(strict_types=1);

namespace LPWork\Validation\Context;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Validation\Exceptions\ValidationException;

/**
 * Registers validation debug context provider services with the framework container.
 */
final readonly class ValidationDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        $throwable = $context->throwable();

        if (!$throwable instanceof ValidationException) {
            return [];
        }

        return [
            'Validation' => [
                'Errors' => $throwable->errors()->toArray(),
            ],
        ];
    }
}
