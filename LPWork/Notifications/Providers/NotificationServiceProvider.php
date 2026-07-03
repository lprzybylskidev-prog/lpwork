<?php

declare(strict_types=1);

namespace LPWork\Notifications\Providers;

use LPWork\Broadcasting\BroadcastManager;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\NotificationsHealthCheck;
use LPWork\Mail\MailManager;
use LPWork\Notifications\Channels\BroadcastNotificationChannel;
use LPWork\Notifications\Channels\DatabaseNotificationChannel;
use LPWork\Notifications\Channels\MailNotificationChannel;
use LPWork\Notifications\Exceptions\InvalidNotificationConfigException;
use LPWork\Notifications\Exceptions\MissingNotificationConfigException;
use LPWork\Notifications\Migrations\CreateNotificationsTable;
use LPWork\Notifications\NotificationChannelRegistry;
use LPWork\Notifications\NotificationDatabaseRepository;
use LPWork\Notifications\NotificationManager;
use LPWork\Queue\QueueManager;
use LPWork\Time\Contracts\Clock;

/**
 * Registers notification service provider services with the framework container.
 */
final class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(NotificationChannelRegistry::class, static function (Container $container): NotificationChannelRegistry {
            $registry = new NotificationChannelRegistry();

            $mail = $container->make(MailManager::class);
            $repository = $container->make(NotificationDatabaseRepository::class);
            $clock = $container->make(Clock::class);
            $broadcasts = $container->make(BroadcastManager::class);

            if (!$mail instanceof MailManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MailManager::class);
            }

            if (!$repository instanceof NotificationDatabaseRepository) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(NotificationDatabaseRepository::class);
            }

            if (!$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            if (!$broadcasts instanceof BroadcastManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(BroadcastManager::class);
            }

            $registry->add('mail', new MailNotificationChannel($mail));
            $registry->add('database', new DatabaseNotificationChannel($repository, $clock));
            $registry->add('broadcast', new BroadcastNotificationChannel($broadcasts));

            return $registry;
        });

        $container->singleton(NotificationDatabaseRepository::class, static function (Container $container): NotificationDatabaseRepository {
            $database = $container->make(DatabaseManager::class);

            if (!$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            return new NotificationDatabaseRepository(
                db: $database->connection(self::databaseReader()->string('connection', 'database.connection')),
                table: self::databaseReader()->string('table', 'database.table'),
            );
        });

        $container->singleton(NotificationManager::class, static function (Container $container): NotificationManager {
            $channels = $container->make(NotificationChannelRegistry::class);
            $queue = self::optional($container, QueueManager::class);
            $events = self::optional($container, EventDispatcher::class);

            if (!$channels instanceof NotificationChannelRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(NotificationChannelRegistry::class);
            }

            return new NotificationManager(
                channels: $channels,
                queue: $queue instanceof QueueManager ? $queue : null,
                events: $events instanceof EventDispatcher ? $events : null,
            );
        });

        $container->singleton(NotificationsHealthCheck::class, static fn(Container $container): NotificationsHealthCheck => new NotificationsHealthCheck($container));
        $this->registerHealthCheck($container, NotificationsHealthCheck::class);
        $this->registerNotificationMigrations($container);
    }

    private function registerNotificationMigrations(Container $container): void
    {
        $database = self::databaseReader();

        $table = $database->string('table', 'database.table');
        $container->singleton(CreateNotificationsTable::class, static fn(): CreateNotificationsTable => new CreateNotificationsTable($table));
        parent::registerFrameworkMigrations($container, $database->string('connection', 'database.connection'), [CreateNotificationsTable::class]);
    }

    private static function databaseReader(): ArrayConfigReader
    {
        return self::reader(self::reader(Config::getArray('notifications'))->array('database'));
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingNotificationConfigException => new MissingNotificationConfigException($key),
            invalidException: static fn(string $key): InvalidNotificationConfigException => new InvalidNotificationConfigException($key),
        );
    }
}
