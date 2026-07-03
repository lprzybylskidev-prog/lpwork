<?php

declare(strict_types=1);

namespace Tests\support\mail;

use LPWork\Logging\LogChannel;
use LPWork\Mail\MailManager;
use LPWork\Mail\MailTransportFactory;
use Tests\support\logging\InMemoryLogDriver;

final readonly class MailTestFactory
{
    /**
     * @return array{0: MailManager, 1: InMemoryLogDriver}
     */
    public static function manager(bool $appDebug = false): array
    {
        $driver = new InMemoryLogDriver();
        $logger = new LogChannel('mail', $driver);

        return [
            new MailManager(
                config: self::config(),
                transportFactory: new MailTransportFactory($logger, $appDebug),
                logger: $logger,
                appDebug: $appDebug,
            ),
            $driver,
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function config(): array
    {
        return [
            'default' => 'log',
            'default_sender' => 'app',
            'senders' => [
                'app' => [
                    'address' => 'hello@example.test',
                    'name' => 'LPWork App',
                ],
                'support' => [
                    'address' => 'support@example.test',
                    'name' => 'LPWork Support',
                ],
            ],
            'transports' => [
                'log' => [
                    'driver' => 'log',
                ],
                'smtp' => [
                    'driver' => 'smtp',
                    'host' => 'smtp.example.test',
                    'port' => 587,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'timeout_seconds' => 30,
                ],
            ],
            'logging' => [
                'enabled' => true,
                'channel' => 'mail',
                'level' => 'info',
            ],
        ];
    }
}
