<?php

declare(strict_types=1);

use LPWork\Foundation\Providers\FoundationServiceProvider;
use Tests\support\container\AlternativeBoundService;
use Tests\support\container\BoundService;
use Tests\support\container\ContextualDependencyService;
use Tests\support\container\DependentService;
use Tests\support\container\ServiceContract;
use Tests\support\container\SimpleService;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Container\TestContainer;

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('builds a standalone container and asserts resolved implementations', function (): void {
    TestContainer::create()
        ->bind(ServiceContract::class, BoundService::class)
        ->assertResolvesTo(ServiceContract::class, BoundService::class)
        ->assertResolvesWith(DependentService::class, static function (object $resolved): void {
            expect($resolved)->toBeInstanceOf(DependentService::class);

            if ($resolved instanceof DependentService) {
                expect($resolved->service)->toBeInstanceOf(SimpleService::class);
            }
        });
});

it('overrides bindings with explicit instances', function (): void {
    $service = new BoundService();

    TestContainer::create()
        ->bind(ServiceContract::class, BoundService::class)
        ->instance(ServiceContract::class, $service)
        ->assertResolvesSame(ServiceContract::class, $service);
});

it('asserts contextual bindings without repeating container setup', function (): void {
    TestContainer::create()
        ->bind(ServiceContract::class, BoundService::class)
        ->contextual(ContextualDependencyService::class, ServiceContract::class, AlternativeBoundService::class)
        ->assertResolvesTo(ServiceContract::class, BoundService::class)
        ->assertContextualDependency(ContextualDependencyService::class, 'service', AlternativeBoundService::class);
});

it('wraps an application container', function (): void {
    $harness = ApplicationTestHarness::create();

    $harness
        ->register(new FoundationServiceProvider($harness->application()))
        ->testContainer()
        ->assertResolvesSame(LPWork\Foundation\Application::class, $harness->application());
});
