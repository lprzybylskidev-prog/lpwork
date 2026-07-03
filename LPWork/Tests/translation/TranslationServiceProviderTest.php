<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\Providers\TranslationServiceProvider;
use LPWork\Translation\Translator;
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

it('defines the translation service provider', function (): void {
    expect(new TranslationServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers translator services from application configuration', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_LANG', 'pl_PL');
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new FoundationServiceProvider($app));
    $app->register(new ConfigsProvider($app));
    $app->register(new TranslationServiceProvider());

    $translator = $app->container()->make(Translator::class);

    expect($app->container()->make(JsonTranslationLoader::class))->toBeInstanceOf(JsonTranslationLoader::class)
        ->and($translator)->toBeInstanceOf(Translator::class);

    if (!$translator instanceof Translator) {
        return;
    }

    expect($translator->get('validation.required', ['field' => 'email']))->toBe('Pole email jest wymagane.');
});
