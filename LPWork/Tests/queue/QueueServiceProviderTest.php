<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Container\Container;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Database\Providers\DatabaseServiceProvider;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Queue\Migrations\CreateQueueJobsTable;
use LPWork\Queue\Providers\QueueServiceProvider;
use LPWork\Queue\QueueManager;
use LPWork\Queue\QueuePruner;
use LPWork\Queue\QueueWorker;
use LPWork\Time\Providers\TimeServiceProvider;

beforeEach(function (): void {
    Config::reset();
});

it('registers database queue schema as a framework migration', function (): void {
    Config::initSource(new class implements ConfigSource {
        /**
         * @return array<string, array<array-key, mixed>>
         */
        public function load(): array
        {
            return [
                'app' => [
                    'debug' => false,
                    'timezone' => 'UTC',
                ],
                'database' => [
                    'default' => 'sqlite',
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                    'logging' => [
                        'enabled' => false,
                        'channel' => 'app',
                        'level' => 'debug',
                    ],
                ],
                'queue' => [
                    'default' => 'database',
                    'queue' => 'default',
                    'retry' => [
                        'max_attempts' => 3,
                        'retry_after_seconds' => 90,
                        'delay_seconds' => 5,
                    ],
                    'retention' => [
                        'completed_seconds' => 60,
                        'failed_seconds' => 60,
                    ],
                    'connections' => [
                        'database' => [
                            'driver' => 'database',
                            'connection' => 'sqlite',
                            'table' => 'queue_jobs',
                        ],
                    ],
                ],
            ];
        }
    });

    $container = new Container();
    $container->instance(Application::class, new Application(\Tests\support\ProjectPaths::root(), $container));
    $container->singleton(MigrationRegistry::class);
    new TimeServiceProvider()->register($container);
    new DatabaseServiceProvider()->register($container);
    new QueueServiceProvider()->register($container);
    $registry = $container->make(MigrationRegistry::class);

    expect($registry)->toBeInstanceOf(MigrationRegistry::class);

    if (!$registry instanceof MigrationRegistry) {
        return;
    }

    expect($registry->forConnection('sqlite'))->toBe([CreateQueueJobsTable::class]);
});

it('does not register queue database migrations for the default sync connection', function (): void {
    Config::initSource(new class implements ConfigSource {
        /**
         * @return array<string, array<array-key, mixed>>
         */
        public function load(): array
        {
            return [
                'app' => [
                    'debug' => false,
                    'timezone' => 'UTC',
                ],
                'database' => [
                    'default' => 'sqlite',
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                    'logging' => [
                        'enabled' => false,
                        'channel' => 'app',
                        'level' => 'debug',
                    ],
                ],
                'queue' => [
                    'default' => 'sync',
                    'queue' => 'default',
                    'retry' => [
                        'max_attempts' => 3,
                        'retry_after_seconds' => 90,
                        'delay_seconds' => 5,
                    ],
                    'retention' => [
                        'completed_seconds' => 60,
                        'failed_seconds' => 60,
                    ],
                    'connections' => [
                        'sync' => [
                            'driver' => 'sync',
                        ],
                    ],
                ],
            ];
        }
    });

    $container = new Container();
    $container->instance(Application::class, new Application(\Tests\support\ProjectPaths::root(), $container));
    $container->singleton(MigrationRegistry::class);
    new TimeServiceProvider()->register($container);
    new DatabaseServiceProvider()->register($container);
    new QueueServiceProvider()->register($container);
    $registry = $container->make(MigrationRegistry::class);

    expect($registry)->toBeInstanceOf(MigrationRegistry::class);

    if (!$registry instanceof MigrationRegistry) {
        return;
    }

    expect($registry->connectionNames())->toBe([]);
});

afterEach(function (): void {
    Config::reset();
});

it('defines the queue service provider', function (): void {
    expect(new QueueServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers queue services as singletons', function (): void {
    Config::initSource(new class implements ConfigSource {
        /**
         * @return array<string, array<array-key, mixed>>
         */
        public function load(): array
        {
            return [
                'app' => [
                    'debug' => false,
                    'timezone' => 'UTC',
                ],
                'database' => [
                    'default' => 'sqlite',
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                    'logging' => [
                        'enabled' => false,
                        'channel' => 'app',
                        'level' => 'debug',
                    ],
                ],
                'queue' => [
                    'default' => 'sync',
                    'queue' => 'default',
                    'retry' => [
                        'max_attempts' => 3,
                        'retry_after_seconds' => 90,
                        'delay_seconds' => 5,
                    ],
                    'retention' => [
                        'completed_seconds' => 60,
                        'failed_seconds' => 60,
                    ],
                    'connections' => [
                        'sync' => [
                            'driver' => 'sync',
                        ],
                    ],
                ],
            ];
        }
    });

    $container = new Container();
    $container->instance(Application::class, new Application(\Tests\support\ProjectPaths::root(), $container));
    new TimeServiceProvider()->register($container);
    new DatabaseServiceProvider()->register($container);
    new QueueServiceProvider()->register($container);

    expect($container->make(QueueManager::class))
        ->toBeInstanceOf(QueueManager::class)
        ->toBe($container->make(QueueManager::class))
        ->and($container->make(QueueWorker::class))
        ->toBeInstanceOf(QueueWorker::class)
        ->toBe($container->make(QueueWorker::class))
        ->and($container->make(QueuePruner::class))
        ->toBeInstanceOf(QueuePruner::class)
        ->toBe($container->make(QueuePruner::class));
});
