<?php

declare(strict_types=1);

namespace LPWork\Bootstrap;

use LPWork\Broadcasting\Providers\BroadcastingServiceProvider;
use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Console\Providers\ConsoleServiceProvider;
use LPWork\Database\Migrations\Providers\MigrationServiceProvider;
use LPWork\Database\Providers\DatabaseServiceProvider;
use LPWork\Database\Seeders\Providers\SeederServiceProvider;
use LPWork\DebugBar\Providers\DebugBarRoutesProvider;
use LPWork\DebugBar\Providers\DebugBarServiceProvider;
use LPWork\DebugDump\Providers\DebugDumpServiceProvider;
use LPWork\Emitters\Providers\EmitterServiceProvider;
use LPWork\ErrorHandling\Providers\ErrorHandlingServiceProvider;
use LPWork\ErrorHandling\Providers\ErrorRoutesProvider;
use LPWork\Events\Providers\EventServiceProvider;
use LPWork\Faker\Providers\FakerServiceProvider;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Frontend\Providers\ApplicationAssetViewProvider;
use LPWork\Frontend\Providers\FrontendServiceProvider;
use LPWork\Health\Providers\HealthRoutesProvider;
use LPWork\Health\Providers\HealthServiceProvider;
use LPWork\Locks\Providers\LockServiceProvider;
use LPWork\Logging\Providers\LoggingServiceProvider;
use LPWork\Mail\Providers\MailServiceProvider;
use LPWork\Maintenance\Providers\MaintenanceRoutesProvider;
use LPWork\Maintenance\Providers\MaintenanceServiceProvider;
use LPWork\Notifications\Providers\NotificationServiceProvider;
use LPWork\Observability\Providers\ObservabilityServiceProvider;
use LPWork\Queue\Providers\QueueServiceProvider;
use LPWork\Routing\Providers\RoutingServiceProvider;
use LPWork\Schedule\Providers\SchedulerServiceProvider;
use LPWork\Security\Providers\SecurityServiceProvider;
use LPWork\Session\Providers\SessionServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;
use LPWork\Throttle\Providers\ThrottleServiceProvider;
use LPWork\Time\Providers\TimeServiceProvider;
use LPWork\Translation\Providers\TranslationServiceProvider;
use LPWork\Validation\Providers\ValidationServiceProvider;
use LPWork\View\Providers\ViewServiceProvider;

/**
 * Registers the framework providers that make up a fully booted LPWork application.
 */
final readonly class FrameworkServiceProviderRegistrar
{
    /**
     * Registers framework providers around the application provider in the order required by runtime boot.
     */
    public function register(Application $app, ServiceProvider $applicationProvider): void
    {
        foreach ($this->coreProviders() as $provider) {
            $app->register($provider);
        }

        $app->register($applicationProvider);

        foreach ($this->routeProviders() as $provider) {
            $app->register($provider);
        }
    }

    /**
     * @return list<ServiceProvider>
     */
    private function coreProviders(): array
    {
        return [
            new HealthServiceProvider(),
            new StorageServiceProvider(),
            new TimeServiceProvider(),
            new CacheServiceProvider(),
            new LockServiceProvider(),
            new FrontendServiceProvider(),
            new ConsoleServiceProvider(),
            new EmitterServiceProvider(),
            new RoutingServiceProvider(),
            new MaintenanceServiceProvider(),
            new ObservabilityServiceProvider(),
            new LoggingServiceProvider(),
            new MailServiceProvider(),
            new ErrorHandlingServiceProvider(),
            new EventServiceProvider(),
            new BroadcastingServiceProvider(),
            new FakerServiceProvider(),
            new DatabaseServiceProvider(),
            new SeederServiceProvider(),
            new MigrationServiceProvider(),
            new QueueServiceProvider(),
            new NotificationServiceProvider(),
            new SchedulerServiceProvider(),
            new SecurityServiceProvider(),
            new SessionServiceProvider(),
            new ThrottleServiceProvider(),
            new TranslationServiceProvider(),
            new ValidationServiceProvider(),
            new ViewServiceProvider(),
            new ApplicationAssetViewProvider(),
            new DebugBarServiceProvider(),
            new DebugDumpServiceProvider(),
        ];
    }

    /**
     * @return list<ServiceProvider>
     */
    private function routeProviders(): array
    {
        return [
            new DebugBarRoutesProvider(),
            new HealthRoutesProvider(),
            new MaintenanceRoutesProvider(),
            new ErrorRoutesProvider(),
        ];
    }
}
