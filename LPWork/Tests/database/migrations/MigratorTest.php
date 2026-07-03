<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Console\ConsoleMiddlewarePipeline;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Contracts\ProductionSensitiveCommand as ConsoleProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Container\Container;
use LPWork\Database\Migrations\Commands\MigrateCommand;
use LPWork\Database\Migrations\Commands\MigrateFreshCommand;
use LPWork\Database\Migrations\Commands\MigrateRollbackCommand;
use LPWork\Database\Migrations\Commands\MigrateStatusCommand;
use LPWork\Database\Migrations\Exceptions\DuplicateMigrationException;
use LPWork\Database\Migrations\Exceptions\MigrationConnectionNotRegisteredException;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Database\Migrations\MigrationStatusRenderer;
use LPWork\Database\Migrations\Providers\MigrationServiceProvider;
use LPWork\Database\Migrations\Providers\MigrationsProvider;
use LPWork\Database\Seeders\Commands\DatabaseSeedCommand;
use LPWork\Database\Seeders\Exceptions\DuplicateSeederException;
use LPWork\Database\Seeders\Providers\SeederServiceProvider;
use LPWork\Database\Seeders\Providers\SeedersProvider;
use LPWork\Database\Seeders\SeederRegistry;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Responses\ConsoleResponse;
use Tests\support\console\OutputStreams;
use Tests\support\database\migrations\CreateAnalyticsEventsTable;
use Tests\support\database\migrations\CreateMigrationEventsTable;
use Tests\support\database\migrations\InsertFirstMigrationEvent;
use Tests\support\database\migrations\InsertSecondMigrationEvent;
use Tests\support\database\migrations\MigrationTestConfig;
use Tests\support\database\migrations\MigrationTestEnvironment;
use Tests\support\database\migrations\MigrationTestHarness;
use Tests\support\database\migrations\SeedAnalyticsEvents;
use Tests\support\database\migrations\SeederTestHarness;
use Tests\support\database\migrations\SeedMigrationEvents;

afterEach(function (): void {
    Config::reset();
});

it('defines the migration service provider', function (): void {
    expect(new MigrationServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('defines the seeder service provider', function (): void {
    expect(new SeederServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers migrations per connection through explicit providers', function (): void {
    $container = new Container();
    $container->singleton(MigrationRegistry::class);
    $provider = new class extends MigrationsProvider {
        protected function migrations(): array
        {
            return [
                'default' => [
                    CreateMigrationEventsTable::class,
                ],
                'analytics' => [
                    CreateAnalyticsEventsTable::class,
                ],
            ];
        }
    };

    $provider->register($container);
    $registry = $container->make(MigrationRegistry::class);

    expect($registry)->toBeInstanceOf(MigrationRegistry::class);

    if (!$registry instanceof MigrationRegistry) {
        return;
    }

    expect($registry->connectionNames())->toBe(['default', 'analytics'])
        ->and($registry->forConnection('default'))->toBe([CreateMigrationEventsTable::class])
        ->and($registry->forConnection('analytics'))->toBe([CreateAnalyticsEventsTable::class]);
});

it('rejects duplicate migrations for the same connection', function (): void {
    $registry = new MigrationRegistry();
    $registry->add('default', [CreateMigrationEventsTable::class]);

    expect(fn() => $registry->add('default', [CreateMigrationEventsTable::class]))
        ->toThrow(DuplicateMigrationException::class);
});

it('registers seeders per connection through explicit providers', function (): void {
    $container = new Container();
    $container->singleton(SeederRegistry::class);
    $provider = new class extends SeedersProvider {
        protected function seeders(): array
        {
            return [
                'default' => [
                    SeedMigrationEvents::class,
                ],
                'analytics' => [
                    SeedAnalyticsEvents::class,
                ],
            ];
        }
    };

    $provider->register($container);
    $registry = $container->make(SeederRegistry::class);

    expect($registry)->toBeInstanceOf(SeederRegistry::class);

    if (!$registry instanceof SeederRegistry) {
        return;
    }

    expect($registry->connectionNames())->toBe(['default', 'analytics'])
        ->and($registry->forConnection('default'))->toBe([SeedMigrationEvents::class])
        ->and($registry->forConnection('analytics'))->toBe([SeedAnalyticsEvents::class]);
});

it('rejects duplicate seeders for the same connection', function (): void {
    $registry = new SeederRegistry();
    $registry->add('default', [SeedMigrationEvents::class]);

    expect(fn() => $registry->add('default', [SeedMigrationEvents::class]))
        ->toThrow(DuplicateSeederException::class);
});

it('runs pending migrations in provider order and records one batch', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $harness = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
                InsertSecondMigrationEvent::class,
            ],
        ]);
        $migrator = $harness->migrator();

        $executions = $migrator->migrate();
        $connection = $environment->database()->default();

        expect(array_map(static fn($execution): string => $execution->migration, $executions))
            ->toBe([
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
                InsertSecondMigrationEvent::class,
            ])
            ->and($connection->select('select name from migration_events'))->toBe([
                ['name' => 'first'],
                ['name' => 'second'],
            ])
            ->and($connection->select('select migration, batch from migrations order by migration'))->toHaveCount(3);
    } finally {
        $environment->remove();
    }
});

it('does not run already applied migrations again', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
            ],
        ])->migrator();

        $migrator->migrate();

        expect($migrator->migrate())->toBe([]);
    } finally {
        $environment->remove();
    }
});

it('rolls back the latest batch in reverse provider order', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
                InsertSecondMigrationEvent::class,
            ],
        ])->migrator();
        $connection = $environment->database()->default();

        $migrator->migrate();
        $executions = $migrator->rollback();

        expect(array_map(static fn($execution): string => $execution->migration, $executions))
            ->toBe([
                InsertSecondMigrationEvent::class,
                InsertFirstMigrationEvent::class,
                CreateMigrationEventsTable::class,
            ])
            ->and($connection->select('select migration from migrations'))->toBe([]);
    } finally {
        $environment->remove();
    }
});

it('refreshes migrations by rolling back every batch before migrating again', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
            ],
        ])->migrator();
        $connection = $environment->database()->default();

        $migrator->migrate();
        $connection->statement('insert into migration_events (name) values (?)', ['stale']);

        $result = $migrator->fresh();

        expect(array_map(static fn($execution): string => $execution->migration, $result->rolledBack))
            ->toBe([
                InsertFirstMigrationEvent::class,
                CreateMigrationEventsTable::class,
            ])
            ->and(array_map(static fn($execution): string => $execution->migration, $result->migrated))
            ->toBe([
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
            ])
            ->and($connection->select('select name from migration_events'))->toBe([
                ['name' => 'first'],
            ]);
    } finally {
        $environment->remove();
    }
});

it('reports migration status per connection', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
                InsertFirstMigrationEvent::class,
            ],
        ])->migrator();

        $migrator->migrate();
        $statuses = $migrator->status();

        expect($statuses)->toHaveCount(2)
            ->and($statuses[0]->connection)->toBe('default')
            ->and($statuses[0]->ran)->toBeTrue()
            ->and($statuses[0]->batch)->toBe(1);
    } finally {
        $environment->remove();
    }
});

it('runs migrations for all registered connections', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
            'analytics' => [
                CreateAnalyticsEventsTable::class,
            ],
        ])->migrator();

        $migrator->migrate(all: true);
        $database = $environment->database();

        expect($database->default()->select('select * from migration_events'))->toBe([])
            ->and($database->connection('analytics')->select('select name from analytics_events'))->toBe([
                ['name' => 'analytics'],
            ]);
    } finally {
        $environment->remove();
    }
});

it('runs seeders for the selected connection', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator()->migrate();

        $seeder = new SeederTestHarness($environment, [
            'default' => [
                SeedMigrationEvents::class,
            ],
        ])->seeder();

        $executions = $seeder->seed();

        expect(array_map(static fn($execution): string => $execution->seeder, $executions))
            ->toBe([SeedMigrationEvents::class])
            ->and($environment->database()->default()->select('select name from migration_events'))->toBe([
                ['name' => 'seeded'],
            ]);
    } finally {
        $environment->remove();
    }
});

it('runs seeders for all registered connections', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
            'analytics' => [
                CreateAnalyticsEventsTable::class,
            ],
        ])->migrator()->migrate(all: true);

        $seeder = new SeederTestHarness($environment, [
            'default' => [
                SeedMigrationEvents::class,
            ],
            'analytics' => [
                SeedAnalyticsEvents::class,
            ],
        ])->seeder();

        $seeder->seed(all: true);
        $database = $environment->database();

        expect($database->default()->select('select name from migration_events'))->toBe([
            ['name' => 'seeded'],
        ])->and($database->connection('analytics')->select('select name from analytics_events order by name'))->toBe([
            ['name' => 'analytics'],
            ['name' => 'seeded-analytics'],
        ]);
    } finally {
        $environment->remove();
    }
});

it('throws when migrating an unregistered connection', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator();

        expect(fn() => $migrator->migrate('missing'))
            ->toThrow(MigrationConnectionNotRegisteredException::class);
    } finally {
        $environment->remove();
    }
});

it('renders migration status from the status command', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator();
        $streams = OutputStreams::create();
        $command = new MigrateStatusCommand($migrator, new MigrationStatusRenderer(new ConsoleTableRenderer()));

        $command->handle(new Input(['lpwork', 'migrate:status']), new Output($streams->stdout, $streams->stderr, decorated: false));

        expect($streams->stdout())->toContain('Connection')
            ->toContain('default')
            ->toContain(CreateMigrationEventsTable::class)
            ->toContain('no');
    } finally {
        $environment->remove();
    }
});

it('runs migrate and rollback commands', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator();
        $seeder = new SeederTestHarness($environment, [
            'default' => [
                SeedMigrationEvents::class,
            ],
        ])->seeder();
        $streams = OutputStreams::create();
        $output = new Output($streams->stdout, $streams->stderr, decorated: false);

        expect(new MigrateCommand($migrator, $seeder)->handle(new Input(['lpwork', 'migrate']), $output))->toBe(0)
            ->and($streams->stdout())->toContain('Migrated');

        $rollback = new MigrateRollbackCommand($migrator);

        expect($rollback)->toBeInstanceOf(ConsoleProductionSensitiveCommand::class)
            ->and($rollback->handle(new Input(['lpwork', 'migrate:rollback']), $output))->toBe(0)
            ->and($streams->stdout())->toContain('Rolled back');
    } finally {
        $environment->remove();
    }
});

it('runs the migrate fresh command and seeds when requested', function (): void {
    MigrationTestConfig::init();
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator();
        $seeder = new SeederTestHarness($environment, [
            'default' => [
                SeedMigrationEvents::class,
            ],
        ])->seeder();
        $connection = $environment->database()->default();
        $streams = OutputStreams::create();

        $migrator->migrate();
        $connection->statement('insert into migration_events (name) values (?)', ['stale']);

        $command = new MigrateFreshCommand($migrator, $seeder);

        expect($command)->toBeInstanceOf(ConsoleProductionSensitiveCommand::class)
            ->and($command->handle(new Input(['lpwork', 'migrate:fresh', '--seed']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($streams->stdout())->toContain('Rolled back')
            ->toContain('Migrated')
            ->toContain('Seeded')
            ->and($streams->stderr())->toBe('')
            ->and($connection->select('select name from migration_events'))->toBe([
                ['name' => 'seeded'],
            ]);
    } finally {
        $environment->remove();
    }
});

it('blocks migrate fresh in production without force', function (): void {
    MigrationTestConfig::init('production');
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator();
        $seeder = new SeederTestHarness($environment, [])->seeder();
        $command = new MigrateFreshCommand($migrator, $seeder);
        $streams = OutputStreams::create();

        $migrator->migrate();

        $response = new ConsoleMiddlewarePipeline([new ProductionSafetyMiddleware($command, true)])
            ->handle(
                new Input(['lpwork', 'migrate:fresh']),
                static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                    static fn(Output $output): int => $command->handle($input, $output),
                ),
            );

        expect($response->send(new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(1)
            ->and($streams->stdout())->toBe('')
            ->and($streams->stderr())->toBe("Refusing to refresh migrations in production without --force.\n")
            ->and($environment->database()->default()->select('select * from migration_events'))->toBe([]);
    } finally {
        $environment->remove();
    }
});

it('runs seeders from the seed command', function (): void {
    $environment = MigrationTestEnvironment::create();

    try {
        new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator()->migrate();
        $command = new DatabaseSeedCommand(new SeederTestHarness($environment, [
            'default' => [
                SeedMigrationEvents::class,
            ],
        ])->seeder());
        $streams = OutputStreams::create();

        expect($command)->toBeInstanceOf(ConsoleProductionSensitiveCommand::class)
            ->and($command->handle(new Input(['lpwork', 'db:seed']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($streams->stdout())->toContain('Seeded')
            ->and($environment->database()->default()->select('select name from migration_events'))->toBe([
                ['name' => 'seeded'],
            ]);
    } finally {
        $environment->remove();
    }
});

it('runs seeders after migrate when requested', function (): void {
    MigrationTestConfig::init();
    $environment = MigrationTestEnvironment::create();

    try {
        $migrator = new MigrationTestHarness($environment, [
            'default' => [
                CreateMigrationEventsTable::class,
            ],
        ])->migrator();
        $seeder = new SeederTestHarness($environment, [
            'default' => [
                SeedMigrationEvents::class,
            ],
        ])->seeder();
        $streams = OutputStreams::create();

        expect(new MigrateCommand($migrator, $seeder)->handle(new Input(['lpwork', 'migrate', '--seed']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($streams->stdout())->toContain('Migrated')
            ->toContain('Seeded')
            ->and($environment->database()->default()->select('select name from migration_events'))->toBe([
                ['name' => 'seeded'],
            ]);
    } finally {
        $environment->remove();
    }
});

it('blocks migrate seeding in production without force', function (): void {
    MigrationTestConfig::init('production');
    $environment = MigrationTestEnvironment::create();

    try {
        $command = new MigrateCommand(
            new MigrationTestHarness($environment, [
                'default' => [
                    CreateMigrationEventsTable::class,
                ],
            ])->migrator(),
            new SeederTestHarness($environment, [
                'default' => [
                    SeedMigrationEvents::class,
                ],
            ])->seeder(),
        );
        $streams = OutputStreams::create();

        $response = new ConsoleMiddlewarePipeline([new ProductionSafetyMiddleware($command, true)])
            ->handle(
                new Input(['lpwork', 'migrate', '--seed']),
                static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                    static fn(Output $output): int => $command->handle($input, $output),
                ),
            );

        expect($response->send(new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(1)
            ->and($streams->stderr())->toBe("Refusing to seed databases in production without --force.\n");
    } finally {
        $environment->remove();
    }
});
