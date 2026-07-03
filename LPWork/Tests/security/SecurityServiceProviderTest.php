<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Security\ApplicationKey;
use LPWork\Security\Contracts\Signer;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfMiddleware;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Security\Http\ValidateSignedUrlMiddleware;
use LPWork\Security\Providers\SecurityServiceProvider;
use LPWork\Security\SecurityConfig;
use LPWork\Security\SecurityConfigFactory;
use LPWork\Security\SignedUrl;
use LPWork\Security\SignedUrlValidator;
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

it('defines the security service provider', function (): void {
    expect(new SecurityServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers the security config from application configuration', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new ConfigsProvider($app));
    $app->register(new SecurityServiceProvider());

    $container = $app->container();
    $security = $container->make(SecurityConfig::class);

    expect($container->make(SecurityConfigFactory::class))->toBeInstanceOf(SecurityConfigFactory::class)
        ->and($container->make(ApplicationKey::class))->toBeInstanceOf(ApplicationKey::class)
        ->and($container->make(Signer::class))->toBeInstanceOf(Signer::class)
        ->and($container->make(SignedUrl::class))->toBeInstanceOf(SignedUrl::class)
        ->and($container->make(SignedUrlValidator::class))->toBeInstanceOf(SignedUrlValidator::class)
        ->and($container->make(CsrfConfig::class))->toBeInstanceOf(CsrfConfig::class)
        ->and($container->make(CsrfTokenManager::class))->toBeInstanceOf(CsrfTokenManager::class)
        ->and($container->make(CsrfMiddleware::class))->toBeInstanceOf(CsrfMiddleware::class)
        ->and($container->make(ValidateSignedUrlMiddleware::class))->toBeInstanceOf(ValidateSignedUrlMiddleware::class)
        ->and($security)->toBeInstanceOf(SecurityConfig::class);

    if (!$security instanceof SecurityConfig) {
        return;
    }

    expect($security->allowsLocalFlows())->toBeTrue();
});

it('allows production security to be made stricter through env backed config', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_ENV', 'production');
    $environment->setEnvValue('SECURITY_ENFORCE_HTTPS', true);
    $environment->setEnvValue('SECURITY_SEND_HEADERS', true);
    $environment->setEnvValue('SECURITY_SECURE_COOKIES', true);

    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new ConfigsProvider($app));
    $app->register(new SecurityServiceProvider());

    $security = $app->container()->make(SecurityConfig::class);

    expect($security)->toBeInstanceOf(SecurityConfig::class);

    if (!$security instanceof SecurityConfig) {
        return;
    }

    expect($security->environment())->toBe('production')
        ->and($security->allowsLocalFlows())->toBeFalse()
        ->and($security->enforcesHttps())->toBeTrue()
        ->and($security->sendsSecurityHeaders())->toBeTrue()
        ->and($security->usesSecureCookies())->toBeTrue();
});
