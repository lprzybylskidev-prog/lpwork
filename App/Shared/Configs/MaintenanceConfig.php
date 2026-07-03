<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;

/**
 * Configures maintenance-mode state storage and optional maintenance response route.
 */
final class MaintenanceConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'maintenance';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // The maintenance file is project-root-relative and written by maintenance commands.
            'file' => 'storage/framework/maintenance.json',
            'route' => null,
        ];
    }
}
