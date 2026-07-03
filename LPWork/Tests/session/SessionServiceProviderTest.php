<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Container\Container;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Session\Providers\SessionServiceProvider;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('does not register session database migrations for the default php driver', function (): void {
    Config::initSource(new class implements ConfigSource {
        /**
         * @return array<string, array<array-key, mixed>>
         */
        public function load(): array
        {
            return [
                'session' => [
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
                ],
            ];
        }
    });

    $container = new Container();
    $container->singleton(MigrationRegistry::class);
    new SessionServiceProvider()->register($container);
    $registry = $container->make(MigrationRegistry::class);

    expect($registry)->toBeInstanceOf(MigrationRegistry::class);

    if (!$registry instanceof MigrationRegistry) {
        return;
    }

    expect($registry->connectionNames())->toBe([]);
});
