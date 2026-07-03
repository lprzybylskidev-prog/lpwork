<?php

declare(strict_types=1);

namespace Tests\support\session;

final class SessionConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function php(): array
    {
        return [
            'default' => 'php',
            'drivers' => [
                'php' => [
                    'driver' => 'php',
                    'name' => 'LPWORK_SESSION',
                    'lifetime' => 120,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'http_only' => true,
                    'same_site' => 'Lax',
                    'use_strict_mode' => true,
                ],
                'memory' => [
                    'driver' => 'memory',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function memory(): array
    {
        return [
            'default' => 'memory',
            'drivers' => [
                'memory' => [
                    'driver' => 'memory',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public static function withPhpConfig(array $config): array
    {
        $sessionConfig = self::php();
        $drivers = $sessionConfig['drivers'];

        if (!is_array($drivers)) {
            return $sessionConfig;
        }

        $php = $drivers['php'] ?? [];

        if (!is_array($php)) {
            $php = [];
        }

        $drivers['php'] = [...$php, ...$config];
        $sessionConfig['drivers'] = $drivers;

        return $sessionConfig;
    }
}
