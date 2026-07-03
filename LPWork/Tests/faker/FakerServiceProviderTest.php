<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use Faker\Generator;
use Faker\Provider\DateTime;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Faker\Providers\FakerServiceProvider;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use Tests\support\ApplicationTestEnvironment;

beforeEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    DateTime::setDefaultTimezone();
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('defines the faker service provider', function (): void {
    expect(new FakerServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers a faker generator with configured language and timezone', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_LANG', 'pl_PL');
    $environment->setEnvValue('APP_TIMEZONE', 'Europe/Warsaw');
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new FoundationServiceProvider($app));
    $app->register(new ConfigsProvider($app));
    $app->register(new FakerServiceProvider());

    $faker = $app->container()->make(Generator::class);

    expect($faker)->toBeInstanceOf(Generator::class);

    if (!$faker instanceof Generator) {
        return;
    }

    expect($faker->dateTime()->getTimezone()->getName())->toBe('Europe/Warsaw')
        ->and($faker->postcode())->toMatch('/^\d{2}-\d{3}$/');
});
