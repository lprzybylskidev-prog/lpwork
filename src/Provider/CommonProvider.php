<?php
declare(strict_types=1);

namespace LPwork\Provider;

use Carbon\CarbonImmutable;
use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Config\PhpConfigLoader;
use LPwork\Config\PhpConfigRepository;
use LPwork\Config\CachedConfigRepository;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use LPwork\Cache\Exception\CacheConfigurationException;
use LPwork\Database\DatabaseTimezoneConfigurator;
use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Cache\CacheManager;
use LPwork\Cache\DefaultCacheProvider;
use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Event\Contract\EventBusInterface;
use LPwork\Event\Contract\EventProviderInterface;
use LPwork\Event\EventBus;
use LPwork\Event\EventDispatcherFactory;
use LPwork\Http\HttpConfiguration;
use LPwork\Http\Response\JsonResponseFactory;
use LPwork\Mail\MailConfiguration;
use LPwork\Mail\MailerFactory;
use LPwork\Mail\MailManager;
use LPwork\Translation\TranslationConfiguration;
use LPwork\Translation\TranslatorFactory;
use LPwork\Translation\TranslationProvider;
use LPwork\Environment\Env;
use LPwork\Filesystem\FilesystemManager;
use LPwork\Http\Routing\RouteLoader;
use LPwork\Database\Migration\FrameworkMigrationProvider;
use LPwork\Database\Migration\Contract\MigrationProviderInterface;
use LPwork\Database\Migration\MigrationRunner;
use LPwork\Database\Seeder\Contract\SeederProviderInterface;
use LPwork\Database\Seeder\FrameworkSeederProvider;
use LPwork\Database\Seeder\SeederRunner;
use LPwork\Database\Contract\DatabaseConnectionInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Database\DoctrineDatabaseConnection;
use LPwork\Logging\LogConfiguration;
use LPwork\Logging\LogFactory;
use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;
use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\ErrorIdProvider;
use LPwork\ErrorLog\ErrorLogConfiguration;
use LPwork\ErrorLog\ErrorLogWriterFactory;
use LPwork\ErrorLog\ErrorLogger;
use LPwork\Http\Middleware\SessionMiddleware;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionConfigurationException;
use LPwork\Http\Session\RandomSessionIdGenerator;
use LPwork\Http\Session\SessionConfiguration;
use LPwork\Http\Session\SessionManager;
use LPwork\Http\Session\Store\DatabaseSessionStore;
use LPwork\Http\Session\Store\FilesystemSessionStore;
use LPwork\Http\Session\Store\PhpSessionStore;
use LPwork\Http\Session\Store\RedisSessionStore;
use LPwork\Redis\Contract\RedisConnectionInterface;
use LPwork\Redis\RedisConnectionManager;
use LPwork\Provider\Contract\ProviderInterface;
use LPwork\Time\CarbonClock;
use LPwork\Time\TimezoneContext;
use LPwork\Version\FrameworkVersion;
use Psr\Log\LoggerInterface;
use Psr\Clock\ClockInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Translation\TranslatorInterface;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;
use Symfony\Component\Mailer\MailerInterface;

if (!\interface_exists(\Psr\Http\Client\ClientInterface::class)) {
    /** @psalm-suppress UnresolvableInclude */
    require_once \dirname(__DIR__, 2) . '/stubs/psr-http-client.php';
}

/**
 * Registers services shared between HTTP and CLI runtimes.
 */
class CommonProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            Env::class => \DI\factory(static function (): Env {
                /** @var array<string, string> $envVars */
                $envVars = $_ENV;

                return Env::fromArray($envVars);
            }),
            CacheConfiguration::class => \DI\factory(static function (
                Env $env,
            ): CacheConfiguration {
                $configDirectory = \dirname(__DIR__, 2) . '/config/configs';
                $loader = new PhpConfigLoader($env);
                $configs = $loader->loadDirectory($configDirectory);
                $cacheConfig = $configs['cache'] ?? [];

                return new CacheConfiguration((array) $cacheConfig);
            }),
            TranslationConfiguration::class => \DI\factory(static function (
                Env $env,
                CacheConfiguration $cacheConfiguration,
            ): TranslationConfiguration {
                $translationCache = $cacheConfiguration->translations();
                $translationConfig = [
                    'locale' => $env->getString('APP_LOCALE', 'en'),
                    'fallback_locale' => $env->getString('APP_FALLBACK_LOCALE', 'en'),
                    'path' => \dirname(__DIR__, 2) . '/config/lang',
                    'cache_enabled' => (bool) ($translationCache['enabled'] ?? true),
                    'cache_pool' => (string) ($translationCache['pool'] ?? 'filesystem'),
                    'cache_prefix' => (string) ($translationCache['prefix'] ?? 'translations:'),
                ];

                return new TranslationConfiguration($translationConfig);
            }),
            ConfigRepositoryInterface::class => \DI\factory(static function (
                Env $env,
                CacheConfiguration $cacheConfiguration,
            ): ConfigRepositoryInterface {
                $configDirectory = \dirname(__DIR__, 2) . '/config/configs';
                $loader = new PhpConfigLoader($env);
                $configs = $loader->loadDirectory($configDirectory);
                $configCache = $cacheConfiguration->configCache();
                $enabled = (bool) ($configCache['enabled'] ?? false);

                if ($enabled) {
                    $poolName = (string) ($configCache['pool'] ?? 'filesystem');
                    $key = (string) ($configCache['key'] ?? 'config:repository');

                    try {
                        $poolConfig = $cacheConfiguration->pool($poolName);
                        $driver = (string) ($poolConfig['driver'] ?? 'array');

                        if ($driver === 'array') {
                            $defaultTtl = (int) ($poolConfig['default_ttl'] ?? 0);
                            $ttlValue = $defaultTtl > 0 ? $defaultTtl : null;
                            $pool = new ArrayAdapter(
                                storeSerialized: false,
                                defaultLifetime: $ttlValue,
                            );

                            return new CachedConfigRepository($configs, $pool, $key);
                        }

                        if ($driver === 'filesystem') {
                            $defaultTtl = (int) ($poolConfig['default_ttl'] ?? 0);
                            $ttlValue = $defaultTtl > 0 ? $defaultTtl : null;
                            $namespace = (string) ($poolConfig['namespace'] ?? '');
                            $path =
                                (string) ($poolConfig['path'] ??
                                    \dirname(__DIR__, 2) . '/storage/cache');
                            $pool = new FilesystemAdapter($namespace, $ttlValue, $path);

                            return new CachedConfigRepository($configs, $pool, $key);
                        }
                    } catch (CacheConfigurationException) {
                        // fall through to non-cached repository
                    }
                }

                return new PhpConfigRepository($configs);
            }),
            HttpConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): HttpConfiguration {
                $httpConfig = $config->get('http', []);

                return new HttpConfiguration((array) $httpConfig);
            }),
            MailConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): MailConfiguration {
                $mailConfig = $config->get('mail', []);

                return new MailConfiguration((array) $mailConfig);
            }),
            TimezoneContext::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): TimezoneContext {
                $timezone = $config->getString('app.timezone', 'UTC');

                return new TimezoneContext($timezone);
            }),
            \DateTimeZone::class => \DI\factory(static function (
                TimezoneContext $timezoneContext,
            ): \DateTimeZone {
                return $timezoneContext->timezone();
            }),
            ClockInterface::class => \DI\autowire(CarbonClock::class),
            CarbonImmutable::class => \DI\factory(static function (
                TimezoneContext $timezoneContext,
            ): CarbonImmutable {
                return CarbonImmutable::now($timezoneContext->timezone());
            }),
            RedisConnectionManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): RedisConnectionManager {
                $connections = $config->get('redis.connections', []);
                $default = $config->getString('redis.default_connection', 'default');

                return new RedisConnectionManager($connections, $default);
            }),
            RedisConnectionInterface::class => \DI\factory(static function (
                RedisConnectionManager $manager,
                ConfigRepositoryInterface $config,
            ): RedisConnectionInterface {
                $default = $config->getString('redis.default_connection', 'default');

                return $manager->get($default);
            }),
            DatabaseConnectionManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
                DatabaseTimezoneConfigurator $timezoneConfigurator,
            ): DatabaseConnectionManager {
                $connections = $config->get('database.connections', []);
                $default = $config->getString('database.default_connection', 'default');

                return new DatabaseConnectionManager($connections, $default, $timezoneConfigurator);
            }),
            DatabaseConnectionInterface::class => \DI\factory(static function (
                DatabaseConnectionManager $manager,
                ConfigRepositoryInterface $config,
            ): DatabaseConnectionInterface {
                $default = $config->getString('database.default_connection', 'default');

                return $manager->get($default);
            }),
            CacheFactory::class => \DI\autowire(CacheFactory::class),
            CacheItemPoolInterface::class => \DI\factory(static function (
                CacheFactory $factory,
                CacheConfiguration $configuration,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManager $databaseConnections,
            ): CacheItemPoolInterface {
                return $factory->createDefaultPool(
                    $configuration,
                    $redisConnections,
                    $databaseConnections,
                );
            }),
            Psr16Cache::class => \DI\factory(static function (
                CacheItemPoolInterface $pool,
            ): Psr16Cache {
                return new Psr16Cache($pool);
            }),
            CacheProviderInterface::class => \DI\autowire(DefaultCacheProvider::class),
            CacheManager::class => \DI\autowire(CacheManager::class),
            TranslatorFactory::class => \DI\autowire(TranslatorFactory::class),
            TranslationProvider::class => \DI\autowire(TranslationProvider::class),
            TranslatorInterface::class => \DI\factory(static function (
                TranslationProvider $provider,
            ): TranslatorInterface {
                return $provider->createTranslator();
            }),
            FakerGenerator::class => \DI\factory(static function (Env $env): FakerGenerator {
                $locale = $env->getString('APP_LOCALE', 'en');

                return FakerFactory::create($locale);
            }),
            Psr17Factory::class => \DI\factory(static fn(): Psr17Factory => new Psr17Factory()),
            RequestFactoryInterface::class => \DI\get(Psr17Factory::class),
            ResponseFactoryInterface::class => \DI\get(Psr17Factory::class),
            StreamFactoryInterface::class => \DI\get(Psr17Factory::class),
            UriFactoryInterface::class => \DI\get(Psr17Factory::class),
            UploadedFileFactoryInterface::class => \DI\get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => \DI\get(Psr17Factory::class),
            JsonResponseFactory::class => \DI\autowire(JsonResponseFactory::class),
            SymfonyHttpClientInterface::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): SymfonyHttpClientInterface {
                /** @var array<string, mixed> $httpClient */
                $httpClient = (array) $config->get('app.http_client', []);
                $baseUri = \trim((string) ($httpClient['base_uri'] ?? ''));
                $timeout = (float) ($httpClient['timeout'] ?? 30.0);
                $maxRedirects = (int) ($httpClient['max_redirects'] ?? 10);
                $verify = (bool) ($httpClient['verify'] ?? true);
                $headers = (array) ($httpClient['headers'] ?? []);

                $options = [
                    'timeout' => $timeout,
                    'max_redirects' => $maxRedirects,
                    'verify_peer' => $verify,
                    'verify_host' => $verify,
                ];

                if ($baseUri !== '') {
                    $options['base_uri'] = $baseUri;
                }

                if ($headers !== []) {
                    $options['headers'] = $headers;
                }

                return HttpClient::create($options);
            }),
            ClientInterface::class => \DI\factory(static function (
                SymfonyHttpClientInterface $httpClient,
                StreamFactoryInterface $streamFactory,
                ResponseFactoryInterface $responseFactory,
            ): ClientInterface {
                return new Psr18Client($httpClient, $responseFactory, $streamFactory);
            }),
            EventDispatcherFactory::class => \DI\autowire(EventDispatcherFactory::class),
            EventProviderInterface::class => \DI\get(\Config\EventProvider::class),
            EventDispatcher::class => \DI\factory(static function (
                EventDispatcherFactory $factory,
                EventProviderInterface $provider,
            ): EventDispatcher {
                return $factory->create($provider);
            }),
            EventDispatcherInterface::class => \DI\get(EventDispatcher::class),
            ListenerProviderInterface::class => \DI\get(EventDispatcher::class),
            EventBusInterface::class => \DI\autowire(EventBus::class),
            MailerFactory::class => \DI\autowire(MailerFactory::class),
            MailManager::class => \DI\autowire(MailManager::class),
            MailerInterface::class => \DI\factory(static function (
                MailManager $manager,
            ): MailerInterface {
                return $manager->default();
            }),
            FilesystemManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): FilesystemManager {
                $disks = $config->get('filesystem.disks', []);
                $default = $config->getString('filesystem.default_disk', 'local');

                return new FilesystemManager($disks, $default);
            }),
            RouteLoader::class => \DI\autowire(RouteLoader::class)->constructor(
                \dirname(__DIR__, 2) . '/config/routes/routes.php',
                \dirname(__DIR__) . '/Http/Routes/routes.php',
            ),
            FrameworkMigrationProvider::class => \DI\autowire(FrameworkMigrationProvider::class),
            MigrationProviderInterface::class => \DI\get(\Config\MigrationProvider::class),
            MigrationRunner::class => \DI\autowire(MigrationRunner::class)
                ->constructorParameter(
                    'frameworkProvider',
                    \DI\get(FrameworkMigrationProvider::class),
                )
                ->constructorParameter('appProvider', \DI\get(\Config\MigrationProvider::class)),
            FrameworkSeederProvider::class => \DI\autowire(FrameworkSeederProvider::class),
            SeederProviderInterface::class => \DI\get(\Config\SeederProvider::class),
            SeederRunner::class => \DI\autowire(SeederRunner::class)
                ->constructorParameter('frameworkProvider', \DI\get(FrameworkSeederProvider::class))
                ->constructorParameter('appProvider', \DI\get(\Config\SeederProvider::class)),
            ErrorLogConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): ErrorLogConfiguration {
                $errorLogConfig = $config->get('error_log', []);

                return new ErrorLogConfiguration((array) $errorLogConfig);
            }),
            ErrorLogWriterInterface::class => \DI\factory(static function (
                ErrorLogConfiguration $config,
                ErrorLogWriterFactory $factory,
                DatabaseConnectionManager $databaseConnections,
                RedisConnectionManager $redisConnections,
                FilesystemManager $filesystemManager,
            ): ErrorLogWriterInterface {
                return $factory->create(
                    $config,
                    $databaseConnections,
                    $redisConnections,
                    $filesystemManager,
                );
            }),
            ErrorIdProviderInterface::class => \DI\autowire(ErrorIdProvider::class),
            ErrorLoggerInterface::class => \DI\autowire(ErrorLogger::class),
            LogConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): LogConfiguration {
                $loggingConfig = $config->get('logging', []);

                return new LogConfiguration((array) $loggingConfig);
            }),
            LogFactory::class => \DI\autowire(LogFactory::class),
            LoggerInterface::class => \DI\factory(static function (
                LogFactory $factory,
                LogConfiguration $configuration,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManager $databaseConnections,
                CarbonImmutable $now,
            ): LoggerInterface {
                return $factory->createDefault(
                    $configuration,
                    $redisConnections,
                    $databaseConnections,
                    $now,
                );
            }),
            SessionConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): SessionConfiguration {
                $sessionConfig = $config->get('session', []);

                return new SessionConfiguration((array) $sessionConfig);
            }),
            SessionIdGeneratorInterface::class => \DI\autowire(RandomSessionIdGenerator::class),
            SessionStoreInterface::class => \DI\factory(static function (
                SessionConfiguration $config,
                SessionIdGeneratorInterface $idGenerator,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManager $databaseConnections,
                FilesystemManager $filesystemManager,
                ClockInterface $clock,
            ): SessionStoreInterface {
                $driver = $config->driver();

                if ($driver === 'php') {
                    $phpConfig = $config->driverConfig('php');
                    $name = (string) ($phpConfig['name'] ?? 'LPWORKSESSID');

                    return new PhpSessionStore($name, $clock);
                }

                if ($driver === 'redis') {
                    $redisConfig = $config->driverConfig('redis');
                    $connection = (string) ($redisConfig['connection'] ?? 'default');
                    $prefix = (string) ($redisConfig['prefix'] ?? 'session:');

                    return new RedisSessionStore(
                        $redisConnections,
                        $connection,
                        $prefix,
                        $idGenerator,
                        $clock,
                    );
                }

                if ($driver === 'database') {
                    $dbConfig = $config->driverConfig('database');
                    $connection = (string) ($dbConfig['connection'] ?? 'default');
                    $table = (string) ($dbConfig['table'] ?? 'sessions');

                    return new DatabaseSessionStore(
                        $databaseConnections,
                        $connection,
                        $table,
                        $idGenerator,
                        $clock,
                    );
                }

                if ($driver === 'filesystem') {
                    $fsConfig = $config->driverConfig('filesystem');
                    $disk = (string) ($fsConfig['disk'] ?? 'local');
                    $path = (string) ($fsConfig['path'] ?? 'sessions');

                    return new FilesystemSessionStore(
                        $filesystemManager,
                        $disk,
                        $path,
                        $idGenerator,
                        $clock,
                    );
                }

                throw new SessionConfigurationException(
                    \sprintf('Session driver "%s" is not supported.', $driver),
                );
            }),
            SessionManager::class => \DI\autowire(SessionManager::class),
            SessionInterface::class => \DI\factory(static function (
                SessionManager $manager,
            ): SessionInterface {
                return $manager->current();
            }),
            SessionMiddleware::class => \DI\autowire(SessionMiddleware::class),
            FrameworkVersion::class => \DI\autowire(FrameworkVersion::class),
        ]);
    }
}
