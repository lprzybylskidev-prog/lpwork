<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures database-backed notification storage.
 */
final class NotificationsConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'notifications';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            'database' => [
                // Notifications default to the main database connection and notifications table.
                'connection' => Environment::get('NOTIFICATIONS_DB_CONNECTION', Environment::getString('DB_CONNECTION')),
                'table' => Environment::get('NOTIFICATIONS_TABLE', 'notifications'),
            ],
        ];
    }
}
