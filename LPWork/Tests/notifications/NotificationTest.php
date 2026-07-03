<?php

declare(strict_types=1);

use LPWork\Broadcasting\BroadcastDriverFactory;
use LPWork\Broadcasting\BroadcastManager;
use LPWork\Broadcasting\Drivers\InMemoryBroadcaster;
use LPWork\Events\EventDebugCollector;
use LPWork\Events\EventDispatcher;
use LPWork\Events\EventRegistry;
use LPWork\Events\ListenerResolver;
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\Events\NotificationQueued;
use LPWork\Notifications\Exceptions\InvalidNotifiableException;
use LPWork\Notifications\Migrations\CreateNotificationsTable;
use LPWork\Notifications\NotificationDatabaseRepository;
use LPWork\Notifications\NotificationManager;
use LPWork\Notifications\NotificationRoutes;
use LPWork\Notifications\NotificationSendOptions;
use LPWork\Queue\QueueDriverFactory;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\QueueManager;
use LPWork\Time\SystemClock;
use Tests\support\ApplicationFactory;
use Tests\support\database\SqliteDatabase;
use Tests\support\mail\MailTestFactory;
use Tests\support\notifications\NotificationTestFactory;
use Tests\support\notifications\TestNotifiable;
use Tests\support\notifications\WelcomeNotification;

it('sends notifications through mail database and broadcast channels', function (): void {
    [$mail, $mailLog] = MailTestFactory::manager(appDebug: true);
    $database = SqliteDatabase::create();
    $databaseManager = NotificationTestFactory::databaseManager($database);
    $connection = $databaseManager->default();
    new CreateNotificationsTable('notifications')->up($connection);
    $repository = new NotificationDatabaseRepository($connection, 'notifications');
    $broadcasts = new BroadcastManager([
        'default' => 'sync',
        'connections' => ['sync' => ['driver' => 'sync']],
    ], new BroadcastDriverFactory());
    $manager = new NotificationManager(NotificationTestFactory::registry($mail, $repository, $broadcasts));

    $result = $manager->send(new TestNotifiable(), new WelcomeNotification(), new NotificationSendOptions(queue: false));
    $broadcaster = $broadcasts->broadcaster('sync');

    if (!$broadcaster instanceof InMemoryBroadcaster) {
        throw new RuntimeException('Expected in-memory broadcaster.');
    }

    expect($result->channels)->toHaveCount(3)
        ->and($mailLog->records)->not->toBeEmpty()
        ->and($repository->unreadForNotifiable('user', '42'))->toHaveCount(1)
        ->and($broadcaster->messages())->toHaveCount(1);
});

it('marks stored database notifications as read', function (): void {
    $database = SqliteDatabase::create();
    $databaseManager = NotificationTestFactory::databaseManager($database);
    $connection = $databaseManager->default();
    new CreateNotificationsTable('notifications')->up($connection);
    $repository = new NotificationDatabaseRepository($connection, 'notifications');

    $id = $repository->store('user', '42', WelcomeNotification::class, ['message' => 'Welcome'], 100);

    expect($repository->unreadForNotifiable('user', '42'))->toHaveCount(1)
        ->and($repository->markAsRead($id, 200))->toBe(1)
        ->and($repository->unreadForNotifiable('user', '42'))->toHaveCount(0)
        ->and($repository->forNotifiable('user', '42')[0]->isRead())->toBeTrue();
});

it('queues queueable notifications through explicit delivery jobs', function (): void {
    [$mail] = MailTestFactory::manager(appDebug: true);
    $database = SqliteDatabase::create();
    $databaseManager = NotificationTestFactory::databaseManager($database);
    $connection = $databaseManager->default();
    new CreateNotificationsTable('notifications')->up($connection);
    $repository = new NotificationDatabaseRepository($connection, 'notifications');
    $broadcasts = new BroadcastManager([
        'default' => 'sync',
        'connections' => ['sync' => ['driver' => 'sync']],
    ], new BroadcastDriverFactory());
    $app = ApplicationFactory::create(__DIR__);
    $clock = new SystemClock();
    $queue = new QueueManager(
        config: [
            'default' => 'sync',
            'queue' => 'default',
            'connections' => ['sync' => ['driver' => 'sync']],
            'retry' => ['max_attempts' => 1, 'retry_after_seconds' => 60, 'delay_seconds' => 0],
            'retention' => ['completed_seconds' => 60, 'failed_seconds' => 60],
        ],
        driverFactory: new QueueDriverFactory(new QueueJobRunner($app->container()), $clock, $databaseManager),
        clock: $clock,
    );
    $manager = new NotificationManager(NotificationTestFactory::registry($mail, $repository, $broadcasts), $queue);
    $app->container()->instance(NotificationManager::class, $manager);

    $result = $manager->send(new TestNotifiable(), new WelcomeNotification());

    expect($result->channels)->toHaveCount(3)
        ->and($result->channels[0]->status)->toBe('queued')
        ->and($repository->unreadForNotifiable('user', '42'))->toHaveCount(1);
});

it('requires an explicit queueable notifiable payload before queueing notifications', function (): void {
    [$mail] = MailTestFactory::manager(appDebug: true);
    $database = SqliteDatabase::create();
    $databaseManager = NotificationTestFactory::databaseManager($database);
    $connection = $databaseManager->default();
    new CreateNotificationsTable('notifications')->up($connection);
    $repository = new NotificationDatabaseRepository($connection, 'notifications');
    $broadcasts = new BroadcastManager([
        'default' => 'sync',
        'connections' => ['sync' => ['driver' => 'sync']],
    ], new BroadcastDriverFactory());
    $app = ApplicationFactory::create(__DIR__);
    $clock = new SystemClock();
    $queue = new QueueManager(
        config: [
            'default' => 'sync',
            'queue' => 'default',
            'connections' => ['sync' => ['driver' => 'sync']],
            'retry' => ['max_attempts' => 1, 'retry_after_seconds' => 60, 'delay_seconds' => 0],
            'retention' => ['completed_seconds' => 60, 'failed_seconds' => 60],
        ],
        driverFactory: new QueueDriverFactory(new QueueJobRunner($app->container()), $clock, $databaseManager),
        clock: $clock,
    );
    $manager = new NotificationManager(NotificationTestFactory::registry($mail, $repository, $broadcasts), $queue);

    $notifiable = new class implements Notifiable {
        public function notificationRoutes(): NotificationRoutes
        {
            return NotificationRoutes::create()->mail('ada@example.test');
        }
    };

    expect(fn() => $manager->send($notifiable, new WelcomeNotification()))
        ->toThrow(InvalidNotifiableException::class, 'must implement QueueableNotifiable');
});

it('dispatches queued notification events', function (): void {
    $registry = new EventRegistry();
    $events = [];
    $registry->add(NotificationQueued::class, [static function (object $event) use (&$events): void {
        $events[] = $event;
    }]);
    $container = ApplicationFactory::create(__DIR__)->container();
    $dispatcher = new EventDispatcher($registry, new ListenerResolver($container), new EventDebugCollector());
    [$mail] = MailTestFactory::manager(appDebug: true);
    $database = SqliteDatabase::create();
    $databaseManager = NotificationTestFactory::databaseManager($database);
    $connection = $databaseManager->default();
    new CreateNotificationsTable('notifications')->up($connection);
    $repository = new NotificationDatabaseRepository($connection, 'notifications');
    $broadcasts = new BroadcastManager([
        'default' => 'sync',
        'connections' => ['sync' => ['driver' => 'sync']],
    ], new BroadcastDriverFactory());
    $app = ApplicationFactory::create(__DIR__);
    $clock = new SystemClock();
    $queue = new QueueManager(
        config: [
            'default' => 'sync',
            'queue' => 'default',
            'connections' => ['sync' => ['driver' => 'sync']],
            'retry' => ['max_attempts' => 1, 'retry_after_seconds' => 60, 'delay_seconds' => 0],
            'retention' => ['completed_seconds' => 60, 'failed_seconds' => 60],
        ],
        driverFactory: new QueueDriverFactory(new QueueJobRunner($app->container()), $clock, $databaseManager),
        clock: $clock,
    );
    $manager = new NotificationManager(NotificationTestFactory::registry($mail, $repository, $broadcasts), $queue, $dispatcher);
    $app->container()->instance(NotificationManager::class, $manager);

    $manager->send(new TestNotifiable(), new WelcomeNotification());

    expect($events)->toHaveCount(3);
});
