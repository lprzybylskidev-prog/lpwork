<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Security\Http\ValidateSignedUrlMiddleware;

/**
 * Defines global, aliased, and grouped route middleware available to application routes.
 */
final class RoutingConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'routing';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            'middleware' => [
                // Global middleware wraps every HTTP route.
                'global' => [],
                'aliases' => [
                    // The signed alias validates signed and temporary signed URLs.
                    'signed' => ValidateSignedUrlMiddleware::class,
                ],
                // Groups collect reusable middleware stacks such as web or api.
                'groups' => [],
            ],
        ];
    }
}
