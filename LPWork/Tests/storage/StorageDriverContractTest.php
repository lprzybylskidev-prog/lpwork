<?php

declare(strict_types=1);

use LPWork\Storage\Drivers\LocalStorageDriver;
use LPWork\Storage\Drivers\MemoryStorageDriver;
use Tests\support\testing\Filesystem\TestFilesystem;
use Tests\support\testing\Storage\StorageDriverContract;

it('keeps the memory storage driver compatible with the shared storage contract', function (): void {
    $contract = new StorageDriverContract(new MemoryStorageDriver());

    $contract->verifiesCoreStorageBehavior();
    $contract->verifiesMissingFilesFail();
});

it('keeps the local storage driver compatible with the shared storage contract', function (): void {
    $files = TestFilesystem::create();

    try {
        $contract = new StorageDriverContract(new LocalStorageDriver($files->root()));

        $contract->verifiesCoreStorageBehavior();
        $contract->verifiesMissingFilesFail();
    } finally {
        $files->cleanup();
    }
});
