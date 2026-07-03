<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Contracts;

use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Defines the contract for http debug context provider.
 */
interface HttpDebugContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array;
}
