<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Contracts\ExceptionRenderer;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\ErrorHandler;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Providers\ErrorHandlingServiceProvider;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Logging\Providers\LoggingServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;
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

it('defines the error handling service provider', function (): void {
    $provider = new ErrorHandlingServiceProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('registers error handling services', function (): void {
    $environment = ApplicationTestEnvironment::create();
    Environment::init($environment->envPath());

    $app = new Application($environment->basePath());
    $app->register(new ConfigsProvider($app));
    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new LoggingServiceProvider());
    $app->register(new ErrorHandlingServiceProvider());

    $container = $app->container();

    expect($container->make(ErrorHandler::class))->toBeInstanceOf(ErrorHandler::class)
        ->and($container->make(ExceptionReporter::class))->toBeInstanceOf(ExceptionReporter::class)
        ->and($container->make(HttpExceptionRenderer::class))->toBeInstanceOf(HttpExceptionRenderer::class)
        ->and($container->make(CliExceptionHandler::class))->toBeInstanceOf(CliExceptionHandler::class)
        ->and($container->make(HttpExceptionHandler::class))->toBeInstanceOf(HttpExceptionHandler::class);
});

it('registers production exception renderers from configuration', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_DEBUG', false);
    Environment::init($environment->envPath());

    $app = new Application($environment->basePath());
    $app->register(new ConfigsProvider($app));
    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new LoggingServiceProvider());
    $app->register(new ErrorHandlingServiceProvider());

    $renderer = $app->container()->make(ExceptionRenderer::class);
    $httpRenderer = $app->container()->make(HttpExceptionRenderer::class);
    $throwable = new RuntimeException('APP_KEY=secret failed');

    expect($renderer)->toBeInstanceOf(ExceptionRenderer::class)
        ->and($httpRenderer)->toBeInstanceOf(HttpExceptionRenderer::class);

    if (!$renderer instanceof ExceptionRenderer || !$httpRenderer instanceof HttpExceptionRenderer) {
        return;
    }

    $httpBody = $httpRenderer->render($throwable)->body();

    expect($renderer->render($throwable))->toBe("Internal Server Error\n")
        ->and($httpBody)->not->toContain('APP_KEY')
        ->and($httpBody)->not->toContain('secret')
        ->and($httpBody)->not->toContain('Stack trace');
});
