<?php

declare(strict_types=1);

namespace Tests\support\notifications;

use LPWork\Broadcasting\BroadcastManager;
use LPWork\Database\DatabaseManager;
use LPWork\Mail\MailManager;
use LPWork\Notifications\Channels\BroadcastNotificationChannel;
use LPWork\Notifications\Channels\DatabaseNotificationChannel;
use LPWork\Notifications\Channels\MailNotificationChannel;
use LPWork\Notifications\NotificationChannelRegistry;
use LPWork\Notifications\NotificationDatabaseRepository;
use LPWork\Time\SystemClock;
use Tests\support\database\SqliteDatabase;

final readonly class NotificationTestFactory
{
    public static function registry(
        MailManager $mail,
        NotificationDatabaseRepository $repository,
        BroadcastManager $broadcasts,
    ): NotificationChannelRegistry {
        $clock = new SystemClock();
        $registry = new NotificationChannelRegistry();
        $registry->add('mail', new MailNotificationChannel($mail));
        $registry->add('database', new DatabaseNotificationChannel($repository, $clock));
        $registry->add('broadcast', new BroadcastNotificationChannel($broadcasts));

        return $registry;
    }

    public static function databaseManager(SqliteDatabase $database): DatabaseManager
    {
        return new DatabaseManager([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $database->relativePath(),
                ],
            ],
        ], $database->basePath());
    }
}
