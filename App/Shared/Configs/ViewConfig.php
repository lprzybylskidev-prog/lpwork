<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;

/**
 * Configures application view paths, compiled view cache store, and PHP view extension.
 */
final class ViewConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'view';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // Application-level views are resolved from these project-root-relative directories.
            'paths' => [
                'resources/views',
            ],
            // The views cache store is declared by CacheConfig.
            'cache_store' => 'views',
            'extension' => 'php',
        ];
    }
}
