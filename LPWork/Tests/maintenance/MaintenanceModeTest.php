<?php

declare(strict_types=1);

use LPWork\Filesystem\Filesystem;
use LPWork\Maintenance\FileMaintenanceStore;
use LPWork\Maintenance\MaintenanceMode;
use LPWork\Maintenance\MaintenanceState;
use Tests\support\testing\ApplicationTestHarness;

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('stores active maintenance state with retry metadata', function (): void {
    $harness = ApplicationTestHarness::create();
    $store = new FileMaintenanceStore(new Filesystem(), $harness->basePath('storage/framework/maintenance.json'));
    $maintenance = new MaintenanceMode($store);

    $state = $maintenance->activate('120');

    expect($state->isActive())->toBeTrue()
        ->and($state->retryAfter())->toBe('120')
        ->and($maintenance->state()->isActive())->toBeTrue()
        ->and($maintenance->state()->retryAfter())->toBe('120');
});

it('clears maintenance state', function (): void {
    $harness = ApplicationTestHarness::create();
    $store = new FileMaintenanceStore(new Filesystem(), $harness->basePath('storage/framework/maintenance.json'));
    $maintenance = new MaintenanceMode($store);

    $maintenance->activate();
    $maintenance->deactivate();

    expect($maintenance->state())->toEqual(MaintenanceState::inactive());
});
