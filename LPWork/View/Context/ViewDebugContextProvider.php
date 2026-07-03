<?php

declare(strict_types=1);

namespace LPWork\View\Context;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\View\ViewDebugCollector;

/**
 * Registers view debug context provider services with the framework container.
 */
final readonly class ViewDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new ViewDebugContextProvider instance.
     */
    public function __construct(
        private ViewDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Views' => [
                'Renders' => $this->collector->renders(),
            ],
        ];
    }
}
