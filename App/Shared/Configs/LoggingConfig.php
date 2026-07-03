<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;

/**
 * Declares default log channels and file-backed fallback logging.
 */
final class LoggingConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'logging';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // The app channel is the default application log target.
            'default' => 'app',
            'channels' => [
                // Stack writes to both application and error channels.
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['app', 'error'],
                ],
                // Fallback channels keep logging available if the primary file cannot be written.
                'app' => [
                    'driver' => 'fallback',
                    'level' => 'debug',
                    'primary' => [
                        'driver' => 'file',
                        'disk' => 'local',
                        'path' => 'logs/app.log',
                        'rotation' => 'daily',
                        'format' => 'line',
                    ],
                    'fallback' => [
                        'driver' => 'file',
                        'disk' => 'local',
                        'path' => 'logs/fallback.log',
                        'format' => 'line',
                    ],
                ],
                'error' => [
                    'driver' => 'fallback',
                    'level' => 'error',
                    'primary' => [
                        'driver' => 'file',
                        'disk' => 'local',
                        'path' => 'logs/error.log',
                        'rotation' => 'daily',
                        'format' => 'line',
                    ],
                    'fallback' => [
                        'driver' => 'file',
                        'disk' => 'local',
                        'path' => 'logs/fallback.log',
                        'format' => 'line',
                    ],
                ],
            ],
        ];
    }
}
