<?php

declare(strict_types=1);

use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Console\CommandRegistry;
use LPWork\Container\Container;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Database\Providers\DatabaseServiceProvider;
use LPWork\Foundation\Application;
use LPWork\Locks\Providers\LockServiceProvider;
use LPWork\Schedule\Commands\ScheduleListCommand;
use LPWork\Schedule\Commands\SchedulePruneCommand;
use LPWork\Schedule\Commands\ScheduleRunCommand;
use LPWork\Schedule\Migrations\CreateScheduleRunsTable;
use LPWork\Schedule\Providers\SchedulerServiceProvider;
use LPWork\Schedule\ScheduleRegistry;
use LPWork\Schedule\ScheduleRunner;
use LPWork\Schedule\ScheduleStore;
use LPWork\Storage\Providers\StorageServiceProvider;
use LPWork\Time\Providers\TimeServiceProvider;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('registers scheduler services, commands, and framework migrations', function (): void {
    Config::initSource(new class implements ConfigSource {
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
                'storage' => [
                    'default' => 'local',
                    'disks' => [
                        'local' => [
                            'driver' => 'local',
                            'root' => 'storage',
                        ],
                    ],
                ],
                'cache' => [
                    'default' => 'framework',
                    'stores' => [
                        'framework' => [
                            'driver' => 'file',
                            'disk' => 'local',
                            'path' => 'framework/cache',
                        ],
                    ],
                ],
                'locks' => [
                    'store' => 'framework',
                    'ttl_seconds' => 900,
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
                'schedule' => [
                    'lock_ttl_seconds' => 900,
                    'database' => [
                        'connection' => 'sqlite',
                        'runs_table' => 'schedule_runs',
                    ],
                    'history' => [
                        'enabled' => true,
                        'retention_seconds' => 60,
                    ],
                ],
            ];
        }
    });

    $container = new Container();
    $container->instance(Application::class, new Application(\Tests\support\ProjectPaths::root(), $container));
    $container->singleton(CommandRegistry::class);
    $container->singleton(MigrationRegistry::class);
    new TimeServiceProvider()->register($container);
    new StorageServiceProvider()->register($container);
    new CacheServiceProvider()->register($container);
    new LockServiceProvider()->register($container);
    new DatabaseServiceProvider()->register($container);
    new \LPWork\Queue\Providers\QueueServiceProvider()->register($container);
    new SchedulerServiceProvider()->register($container);

    $commands = $container->make(CommandRegistry::class);
    $migrations = $container->make(MigrationRegistry::class);

    expect($container->make(ScheduleRegistry::class))->toBeInstanceOf(ScheduleRegistry::class)
        ->and($container->make(ScheduleStore::class))->toBeInstanceOf(ScheduleStore::class)
        ->and($container->make(ScheduleRunner::class))->toBeInstanceOf(ScheduleRunner::class)
        ->and($commands)->toBeInstanceOf(CommandRegistry::class)
        ->and($migrations)->toBeInstanceOf(MigrationRegistry::class);

    if ($commands instanceof CommandRegistry) {
        expect($commands->has('schedule:list'))->toBeTrue()
            ->and($commands->get('schedule:list'))->toBeInstanceOf(ScheduleListCommand::class)
            ->and($commands->get('schedule:run'))->toBeInstanceOf(ScheduleRunCommand::class)
            ->and($commands->get('schedule:prune'))->toBeInstanceOf(SchedulePruneCommand::class);
    }

    if ($migrations instanceof MigrationRegistry) {
        expect($migrations->forConnection('sqlite'))->toContain(CreateScheduleRunsTable::class);
    }
});
