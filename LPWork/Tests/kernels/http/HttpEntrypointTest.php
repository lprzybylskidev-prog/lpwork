<?php

declare(strict_types=1);

use App\Shared\Configs\AppConfig;
use App\Shared\Configs\BroadcastingConfig;
use App\Shared\Configs\CacheConfig;
use App\Shared\Configs\DatabaseConfig;
use App\Shared\Configs\ErrorConfig;
use App\Shared\Configs\LockConfig;
use App\Shared\Configs\LoggingConfig;
use App\Shared\Configs\MailConfig;
use App\Shared\Configs\MaintenanceConfig;
use App\Shared\Configs\NotificationsConfig;
use App\Shared\Configs\QueueConfig;
use App\Shared\Configs\RoutingConfig;
use App\Shared\Configs\ScheduleConfig;
use App\Shared\Configs\SecurityConfig;
use App\Shared\Configs\SessionConfig;
use App\Shared\Configs\StorageConfig;
use App\Shared\Configs\ThrottleConfig;
use App\Shared\Configs\ViewConfig;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Kernels\Http\HttpEntrypoint;
use LPWork\Url\Url;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\testing\Http\CapturingHttpEmitter;

beforeEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

$cachedConfigWithoutNotifications = static function (ApplicationTestEnvironment $environment): array {
    Environment::init($environment->envPath());
    Config::initDefinitions([
        new AppConfig(),
        new BroadcastingConfig(),
        new CacheConfig(),
        new DatabaseConfig(),
        new ErrorConfig(),
        new LockConfig(),
        new LoggingConfig(),
        new MailConfig(),
        new MaintenanceConfig(),
        new NotificationsConfig(),
        new QueueConfig(),
        new RoutingConfig(),
        new ScheduleConfig(),
        new SecurityConfig(),
        new SessionConfig(),
        new StorageConfig(),
        new ThrottleConfig(),
        new ViewConfig(),
    ]);

    $config = Config::all();
    unset($config['notifications']);
    Environment::reset();
    Config::reset();

    return $config;
};

it('renders framework error handling for bootstrap failures after configuration is loaded', function () use ($cachedConfigWithoutNotifications): void {
    $environment = ApplicationTestEnvironment::create();
    $config = $cachedConfigWithoutNotifications($environment);
    $environment->writeFile('storage/framework/cache/config.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n");
    $emitter = new CapturingHttpEmitter();

    $exitCode = new HttpEntrypoint($environment->basePath(), $emitter)->run();

    expect($exitCode)->toBe(500)
        ->and($emitter->response?->statusCode())->toBe(500)
        ->and($emitter->response?->body())->toContain('LPWork debug exception')
        ->and($emitter->response?->body())->toContain('Missing config variable: notifications.');
});
