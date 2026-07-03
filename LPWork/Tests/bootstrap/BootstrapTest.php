<?php

declare(strict_types=1);

use Faker\Generator;
use Faker\Provider\DateTime;
use LPWork\Bootstrap\Bootstrap;
use LPWork\Cache\CacheClearer;
use LPWork\Cache\CacheManager;
use LPWork\Cache\CacheStore;
use LPWork\Config\Config;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Output;
use LPWork\Container\Container;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Emitters\HttpEmitter;
use LPWork\Environment\Environment;
use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\ErrorHandler;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\Events\EventDispatcher;
use LPWork\Events\EventRegistry;
use LPWork\Foundation\Application;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Kernels\Cli\CliKernel;
use LPWork\Locks\AtomicLockManager;
use LPWork\Locks\Contracts\LockStore;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\LogManager;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Queue\QueueManager;
use LPWork\Queue\QueuePruner;
use LPWork\Queue\QueueWorker;
use LPWork\Routing\RouteCollection;
use LPWork\Routing\Router;
use LPWork\Security\ApplicationKey;
use LPWork\Security\Contracts\Signer;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfMiddleware;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Security\Exceptions\InvalidApplicationKeyException;
use LPWork\Security\Http\HttpSecurityMiddleware;
use LPWork\Security\SecurityConfig;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\SessionManager;
use LPWork\Storage\StorageDisk;
use LPWork\Storage\StorageManager;
use LPWork\Throttle\CliThrottle;
use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleConfigFactory;
use LPWork\Throttle\ThrottleLimiter;
use LPWork\Time\Contracts\Clock;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\Translator;
use LPWork\Url\Url;
use LPWork\Url\UrlGenerator;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\OutputStreams;
use Tests\support\middleware\FirstMiddleware;
use Tests\support\middleware\SecondMiddleware;

beforeEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    DateTime::setDefaultTimezone();
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('returns a valid application object after initialization', function (): void {
    $environment = ApplicationTestEnvironment::create();

    $app = Bootstrap::init($environment->basePath());

    expect($app)
        ->toBeInstanceOf(Application::class)
        ->and($app->basePath())->toBe($environment->basePath())
        ->and($app->container())->toBeInstanceOf(Container::class);
});

it('initializes the environment', function (): void {
    $environment = ApplicationTestEnvironment::create();

    $environment->appendEnvValue('TEST', 'test');

    Bootstrap::init($environment->basePath());

    expect(Environment::getString('TEST'))->toBe('test');
});

it('initializes the config', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_LANG', 'en_US');

    Bootstrap::init($environment->basePath());

    expect(Config::getString('app.url'))->toBe('http://localhost')
        ->and(Config::getString('app.lang'))->toBe('en_US')
        ->and(Config::getString('app.timezone'))->toBe('UTC')
        ->and(Config::getString('security.app_key'))->toStartWith('base64:')
        ->and(Config::getString('session.default'))->toBe('php')
        ->and(Config::getString('session.drivers.php.name'))->toBe('LPWORK_SESSION')
        ->and(Config::getInt('session.drivers.php.lifetime'))->toBe(120)
        ->and(Config::getString('view.cache_store'))->toBe('views');
});

it('validates the application key during bootstrap', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_KEY', 'short');

    expect(fn(): Application => Bootstrap::init($environment->basePath()))
        ->toThrow(InvalidApplicationKeyException::class);
});

it('allows the key generation command to bootstrap before APP_KEY exists', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_KEY', '');

    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'key:generate']);

    expect($app)->toBeInstanceOf(Application::class);
});

it('allows completion generation to bootstrap before APP_KEY is valid', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_KEY', 'short');
    $streams = OutputStreams::create();
    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'completion:generate', 'bash']);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'completion:generate', 'bash']))->toBe(0)
        ->and($streams->stdout())->toContain('# LPWork shell completion for bash.')
        ->and($streams->stdout())->toContain('make:controller')
        ->and($streams->stderr())->toBe('');
});

it('keeps framework bootstrap decoupled from direct application imports', function (): void {
    $bootstrap = file_get_contents(\Tests\support\ProjectPaths::root() . '/LPWork/Bootstrap/Bootstrap.php');

    if ($bootstrap === false) {
        throw new RuntimeException('Could not read framework bootstrap file.');
    }

    expect($bootstrap)->not->toContain('use App\\');
});

it('keeps framework service provider registration in a registrar', function (): void {
    $root = \Tests\support\ProjectPaths::root();
    $bootstrap = file_get_contents($root . '/LPWork/Bootstrap/Bootstrap.php');
    $registrar = file_get_contents($root . '/LPWork/Bootstrap/FrameworkServiceProviderRegistrar.php');

    if ($bootstrap === false || $registrar === false) {
        throw new RuntimeException('Could not read framework bootstrap registration files.');
    }

    expect($bootstrap)->toContain('FrameworkServiceProviderRegistrar');
    expect($bootstrap)->not->toContain('new HealthServiceProvider()');
    expect($registrar)->toContain('new HealthServiceProvider()');
    expect($registrar)->toContain('new ErrorRoutesProvider()');
});

it('boots config cache maintenance commands without loading stale compiled cache', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $cache = $environment->writeFile(
        'storage/framework/cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['env' => 'development', 'debug' => true, 'timezone' => 'UTC']];\n",
    );

    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'config:clear']);
    $commands = $app->container()->make(CommandRegistry::class);

    expect($app)->toBeInstanceOf(Application::class)
        ->and(is_file($cache))->toBeTrue()
        ->and($commands)->toBeInstanceOf(CommandRegistry::class);

    if ($commands instanceof CommandRegistry) {
        expect(array_keys($commands->all()))->toBe(['config:cache', 'config:clear']);
    }
});

it('lists only config cache maintenance commands when the compiled cache is stale', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile(
        'storage/framework/cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['env' => 'development', 'debug' => true, 'timezone' => 'UTC']];\n",
    );
    $streams = OutputStreams::create();
    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork']);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork']))->toBe(0)
        ->and($streams->stdout())->toContain('The compiled configuration cache is invalid and must be cleared or rebuilt.')
        ->and($streams->stdout())->toContain('Only config:clear and config:cache are available')
        ->and($streams->stdout())->toContain('config:cache')
        ->and($streams->stdout())->toContain('config:clear')
        ->and($streams->stdout())->not->toContain('queue:work')
        ->and($streams->stdout())->not->toContain('route:list')
        ->and($streams->stderr())->toBe('');
});

it('blocks normal commands with a clear message when the compiled cache is stale', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile(
        'storage/framework/cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['env' => 'development', 'debug' => true, 'timezone' => 'UTC']];\n",
    );
    $streams = OutputStreams::create();
    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'route:list']);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'route:list']))->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain('The compiled configuration cache is invalid and must be cleared or rebuilt.')
        ->and($streams->stderr())->toContain('Only config:clear and config:cache are available')
        ->and($streams->stderr())->toContain('Command not available while the configuration cache is invalid: route:list');
});

it('clears stale compiled config cache through the maintenance console command', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $cache = $environment->writeFile(
        'storage/framework/cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['env' => 'development', 'debug' => true, 'timezone' => 'UTC']];\n",
    );
    $streams = OutputStreams::create();
    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'config:clear']);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'config:clear']))->toBe(0)
        ->and($streams->stdout())->toContain('Configuration cache cleared successfully.')
        ->and(is_file($cache))->toBeFalse()
        ->and($streams->stderr())->toBe('');
});

it('rebuilds stale compiled config cache through the maintenance console command', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $cache = $environment->writeFile(
        'storage/framework/cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['env' => 'development', 'debug' => true, 'timezone' => 'UTC']];\n",
    );
    $streams = OutputStreams::create();
    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'config:cache']);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'config:cache']))->toBe(0)
        ->and($streams->stdout())->toContain('Configuration cache rebuilt successfully.')
        ->and($streams->stderr())->toBe('');

    $cached = include $cache;

    if (!is_array($cached)) {
        throw new RuntimeException('Compiled config cache did not return an array.');
    }

    expect($cached)->toBeArray()
        ->and($cached['database'])->toHaveKey('logging')
        ->and($cached['queue'])->toHaveKey('connections');
});

it('sets error configuration', function (): void {
    $oldErrorReporting = error_reporting();

    $oldDisplayErrors = ini_get('display_errors');
    $oldDisplayStartupErrors = ini_get('display_startup_errors');
    $oldLogErrors = ini_get('log_errors');

    try {
        $environment = ApplicationTestEnvironment::create();

        $environment->setEnvValue('ERROR_REPORTING', E_ALL);
        $environment->setEnvValue('ERROR_DISPLAY', 1);
        $environment->setEnvValue('ERROR_DISPLAY_STARTUP', 1);
        $environment->setEnvValue('ERROR_LOG', 1);

        Bootstrap::init($environment->basePath());

        expect(error_reporting())->toBe(E_ALL);
        expect((int) ini_get('display_errors'))->toBe(1);
        expect((int) ini_get('display_startup_errors'))->toBe(1);
        expect((int) ini_get('log_errors'))->toBe(1);
    } finally {
        error_reporting($oldErrorReporting);

        ini_set('display_errors', $oldDisplayErrors);
        ini_set('display_startup_errors', $oldDisplayStartupErrors);
        ini_set('log_errors', $oldLogErrors);
    }
});

it('registers framework service providers', function (): void {
    $environment = ApplicationTestEnvironment::create();

    $app = Bootstrap::init($environment->basePath());
    $container = $app->container();

    expect($container->make(Container::class))->toBe($container)
        ->and($container->make(Application::class))->toBe($app)
        ->and($container->make(CommandRegistry::class))->toBeInstanceOf(CommandRegistry::class)
        ->and($container->make(ConsoleEmitter::class))->toBeInstanceOf(ConsoleEmitter::class)
        ->and($container->make(HttpEmitter::class))->toBeInstanceOf(HttpEmitter::class)
        ->and($container->make(ErrorHandler::class))->toBeInstanceOf(ErrorHandler::class)
        ->and($container->make(ExceptionReporter::class))->toBeInstanceOf(ExceptionReporter::class)
        ->and($container->make(HttpExceptionRenderer::class))->toBeInstanceOf(HttpExceptionRenderer::class)
        ->and($container->make(CliExceptionHandler::class))->toBeInstanceOf(CliExceptionHandler::class)
        ->and($container->make(HttpExceptionHandler::class))->toBeInstanceOf(HttpExceptionHandler::class)
        ->and($container->make(FrameworkMetadata::class))->toBeInstanceOf(FrameworkMetadata::class)
        ->and($container->make(EventRegistry::class))->toBeInstanceOf(EventRegistry::class)
        ->and($container->make(EventDispatcher::class))->toBeInstanceOf(EventDispatcher::class)
        ->and($container->make(Router::class))->toBeInstanceOf(Router::class)
        ->and($container->make(RouteCollection::class))->toBeInstanceOf(RouteCollection::class)
        ->and($container->make(UrlGenerator::class))->toBeInstanceOf(UrlGenerator::class)
        ->and($container->make(CacheManager::class))->toBeInstanceOf(CacheManager::class)
        ->and($container->make(CacheStore::class))->toBeInstanceOf(CacheStore::class)
        ->and($container->make(CacheClearer::class))->toBeInstanceOf(CacheClearer::class)
        ->and($container->make(LockStore::class))->toBeInstanceOf(LockStore::class)
        ->and($container->make(AtomicLockManager::class))->toBeInstanceOf(AtomicLockManager::class)
        ->and($container->make(StorageManager::class))->toBeInstanceOf(StorageManager::class)
        ->and($container->make(StorageDisk::class))->toBeInstanceOf(StorageDisk::class)
        ->and($container->make(Clock::class))->toBeInstanceOf(Clock::class)
        ->and($container->make(LogManager::class))->toBeInstanceOf(LogManager::class)
        ->and($container->make(Logger::class))->toBeInstanceOf(Logger::class)
        ->and($container->make(QueueManager::class))->toBeInstanceOf(QueueManager::class)
        ->and($container->make(QueueWorker::class))->toBeInstanceOf(QueueWorker::class)
        ->and($container->make(QueuePruner::class))->toBeInstanceOf(QueuePruner::class)
        ->and($container->make(Generator::class))->toBeInstanceOf(Generator::class)
        ->and($container->make(SecurityConfig::class))->toBeInstanceOf(SecurityConfig::class)
        ->and($container->make(ApplicationKey::class))->toBeInstanceOf(ApplicationKey::class)
        ->and($container->make(Signer::class))->toBeInstanceOf(Signer::class)
        ->and($container->make(CsrfConfig::class))->toBeInstanceOf(CsrfConfig::class)
        ->and($container->make(CsrfTokenManager::class))->toBeInstanceOf(CsrfTokenManager::class)
        ->and($container->make(CsrfMiddleware::class))->toBeInstanceOf(CsrfMiddleware::class)
        ->and($container->make(HttpSecurityMiddleware::class))->toBeInstanceOf(HttpSecurityMiddleware::class)
        ->and($container->make(SessionManager::class))->toBeInstanceOf(SessionManager::class)
        ->and($container->make(SessionDriver::class))->toBeInstanceOf(SessionDriver::class)
        ->and($container->make(SessionMiddleware::class))->toBeInstanceOf(SessionMiddleware::class)
        ->and($container->make(ThrottleConfigFactory::class))->toBeInstanceOf(ThrottleConfigFactory::class)
        ->and($container->make(ThrottleConfig::class))->toBeInstanceOf(ThrottleConfig::class)
        ->and($container->make(ThrottleStorage::class))->toBeInstanceOf(ThrottleStorage::class)
        ->and($container->make(ThrottleLimiter::class))->toBeInstanceOf(ThrottleLimiter::class)
        ->and($container->make(CliThrottle::class))->toBeInstanceOf(CliThrottle::class)
        ->and($container->make(JsonTranslationLoader::class))->toBeInstanceOf(JsonTranslationLoader::class)
        ->and($container->make(Translator::class))->toBeInstanceOf(Translator::class)
        ->and($container->make(ViewEngine::class))->toBeInstanceOf(ViewEngine::class)
        ->and($container->make(ViewFinder::class))->toBeInstanceOf(ViewFinder::class)
        ->and($container->make(ViewFactory::class))->toBeInstanceOf(ViewFactory::class);
});

it('registers configured route middleware before loading application route definitions', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->appendConfigValue('routing.php', 'middleware.aliases.first', FirstMiddleware::class);
    $environment->appendConfigValue('routing.php', 'middleware.aliases.second', SecondMiddleware::class);
    $environment->appendConfigValue('routing.php', 'middleware.global', ['first', 'second']);

    $app = Bootstrap::init($environment->basePath());
    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    expect($router->globalMiddlewareList())->toBe([FirstMiddleware::class, SecondMiddleware::class])
        ->and($router->routes()->match('GET', '/')->route()->name())->toBe('home');
});
