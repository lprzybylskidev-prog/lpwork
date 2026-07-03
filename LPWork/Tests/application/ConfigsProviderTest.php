<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Foundation\Contracts\ServiceProvider;
use Tests\support\ApplicationFactory;
use Tests\support\ApplicationTestEnvironment;

beforeEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('defines the application configs provider', function (): void {
    $provider = new ConfigsProvider(ApplicationFactory::create());

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('loads application config definitions', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = ApplicationFactory::create($environment->basePath());

    $environment->setEnvValue('APP_LANG', 'en_US');
    Environment::init($environment->envPath());

    $provider = new ConfigsProvider($app);
    $provider->register($app->container());

    expect(Config::getString('app.url'))->toBe('http://localhost')
        ->and(Config::getString('app.lang'))->toBe('en_US')
        ->and(Config::getString('app.timezone'))->toBe('UTC')
        ->and(Config::getString('session.default'))->toBe('php')
        ->and(Config::has('session.drivers.memory'))->toBeFalse()
        ->and(Config::getString('logging.default'))->toBe('app')
        ->and(Config::getString('database.default'))->toBe('sqlite')
        ->and(Config::getString('database.connections.sqlite.database'))->toBe('storage/database.sqlite')
        ->and(Config::has('database.connections.mysql'))->toBeFalse()
        ->and(Config::has('database.connections.pgsql'))->toBeFalse()
        ->and(Config::getBool('database.logging.enabled'))->toBeFalse()
        ->and(Config::getString('database.logging.channel'))->toBe('app')
        ->and(Config::getString('database.logging.level'))->toBe('debug')
        ->and(Config::getString('cache.default'))->toBe('framework')
        ->and(Config::getString('cache.stores.views.driver'))->toBe('file')
        ->and(Config::getString('storage.default'))->toBe('local')
        ->and(Config::getString('storage.disks.public.url'))->toBe('/storage')
        ->and(Config::has('storage.disks.memory'))->toBeFalse()
        ->and(Config::getString('mail.transports.log.driver'))->toBe('log')
        ->and(Config::has('mail.transports.smtp'))->toBeFalse()
        ->and(Config::getString('queue.connections.sync.driver'))->toBe('sync')
        ->and(Config::has('queue.connections.database'))->toBeFalse()
        ->and(Config::getString('broadcasting.connections.log.driver'))->toBe('log')
        ->and(Config::has('broadcasting.connections.sync'))->toBeFalse()
        ->and(Config::getString('security.app_key'))->toStartWith('base64:')
        ->and(Config::getBool('security.profiles.development.allow_local_flows'))->toBeTrue()
        ->and(Config::getString('throttle.storage'))->toBe('memory')
        ->and(Config::getString('view.cache_store'))->toBe('views')
        ->and(Config::getArray('view.paths'))->toBe(['resources/views'])
        ->and(Config::getString('welcome::app.name'))->toBe('Welcome')
        ->and(Config::getString('welcome::app.url'))->toBe('http://localhost')
        ->and(Config::getString('app.name'))->toBe('LPWork');
});

it('loads cached application config when the compiled cache exists', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = ApplicationFactory::create($environment->basePath());

    $environment->writeFile(
        'storage/framework/cache/config.php',
        <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'app' => [
                    'url' => 'https://cached.example',
                ],
            ];
            PHP,
    );

    Environment::init($environment->envPath());

    $provider = new ConfigsProvider($app);
    $provider->register($app->container());

    expect(Config::getString('app.url'))->toBe('https://cached.example')
        ->and(Config::get('welcome::app.url'))->toBe('https://cached.example');
});

it('loads optional development service configs from environment values', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = ApplicationFactory::create($environment->basePath());

    $environment->setEnvValue('CACHE_STORE', 'redis');
    $environment->setEnvValue('CACHE_REDIS_HOST', 'redis');
    $environment->setEnvValue('CACHE_REDIS_PORT', 6379);
    $environment->setEnvValue('CACHE_REDIS_DATABASE', 2);
    $environment->setEnvValue('CACHE_REDIS_PREFIX', 'lpwork:test:cache:');
    $environment->setEnvValue('THROTTLE_CACHE_STORE', 'redis');
    $environment->setEnvValue('STORAGE_DISK', 's3');
    $environment->setEnvValue('STORAGE_S3_BUCKET', 'lpwork');
    $environment->setEnvValue('STORAGE_S3_REGION', 'us-east-1');
    $environment->setEnvValue('STORAGE_S3_ACCESS_KEY', 'test');
    $environment->setEnvValue('STORAGE_S3_SECRET_KEY', 'test');
    $environment->setEnvValue('STORAGE_S3_ENDPOINT', 'http://localstack:4566');
    $environment->setEnvValue('QUEUE_CONNECTION', 'sqs');
    $environment->setEnvValue('QUEUE_SQS_URL', 'http://localstack:4566/000000000000/lpwork');
    $environment->setEnvValue('QUEUE_SQS_ACCESS_KEY', 'test');
    $environment->setEnvValue('QUEUE_SQS_SECRET_KEY', 'test');
    $environment->setEnvValue('MAIL_TRANSPORT', 'smtp');
    $environment->setEnvValue('MAIL_SMTP_HOST', 'mailpit');
    $environment->setEnvValue('MAIL_SMTP_PORT', 1025);
    $environment->setEnvValue('MAIL_SMTP_ENCRYPTION', '');
    $environment->setEnvValue('BROADCAST_CONNECTION', 'pusher');
    $environment->setEnvValue('PUSHER_APP_ID', 'lpwork');
    $environment->setEnvValue('PUSHER_APP_KEY', 'lpwork-key');
    $environment->setEnvValue('PUSHER_APP_SECRET', 'lpwork-secret');
    $environment->setEnvValue('PUSHER_ENDPOINT', 'http://soketi:6001');
    $environment->setEnvValue('METRICS_REPORTERS', 'statsd');
    $environment->setEnvValue('METRICS_STATSD_HOST', 'statsd');
    $environment->setEnvValue('METRICS_STATSD_PORT', 9125);
    Environment::init($environment->envPath());

    $provider = new ConfigsProvider($app);
    $provider->register($app->container());

    expect(Config::getString('cache.default'))->toBe('redis')
        ->and(Config::getString('cache.stores.redis.driver'))->toBe('redis')
        ->and(Config::getString('cache.stores.redis.host'))->toBe('redis')
        ->and(Config::getInt('cache.stores.redis.database'))->toBe(2)
        ->and(Config::getString('storage.default'))->toBe('s3')
        ->and(Config::getString('storage.disks.s3.driver'))->toBe('s3')
        ->and(Config::getString('storage.disks.s3.endpoint'))->toBe('http://localstack:4566')
        ->and(Config::getString('queue.connections.sqs.driver'))->toBe('sqs')
        ->and(Config::getString('queue.connections.sqs.queue_url'))->toBe('http://localstack:4566/000000000000/lpwork')
        ->and(Config::getString('mail.transports.smtp.driver'))->toBe('smtp')
        ->and(Config::getString('mail.transports.smtp.host'))->toBe('mailpit')
        ->and(Config::getString('broadcasting.connections.pusher.driver'))->toBe('pusher')
        ->and(Config::getString('broadcasting.connections.pusher.endpoint'))->toBe('http://soketi:6001')
        ->and(Config::getString('observability.metrics.reporters.statsd.driver'))->toBe('statsd')
        ->and(Config::getString('observability.metrics.reporters.statsd.host'))->toBe('statsd');
});
