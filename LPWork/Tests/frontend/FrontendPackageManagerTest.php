<?php

declare(strict_types=1);

use LPWork\Bootstrap\Bootstrap;
use LPWork\Frontend\Exceptions\InvalidFrontendCommandException;
use LPWork\Frontend\FrontendPackageManager;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Frontend\FrontendProcessFactory;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\frontend\FrontendPackageManagerTestWorkspace;

afterAll(function (): void {
    FrontendPackageManagerTestWorkspace::clear();
    ApplicationTestEnvironment::removeDirectories();
});

it('detects the frontend package manager from lockfiles', function (string $lockfile, FrontendPackageManager $manager): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();
    $workspace->writeLockfile($lockfile);

    expect(new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files())->detect())->toBe($manager);
})->with([
    'pnpm' => ['pnpm-lock.yaml', FrontendPackageManager::Pnpm],
    'yarn' => ['yarn.lock', FrontendPackageManager::Yarn],
    'bun binary lockfile' => ['bun.lockb', FrontendPackageManager::Bun],
    'bun text lockfile' => ['bun.lock', FrontendPackageManager::Bun],
    'npm' => ['package-lock.json', FrontendPackageManager::Npm],
]);

it('defaults to npm when no package manager lockfile exists', function (): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();

    expect(new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files())->detect())->toBe(FrontendPackageManager::Npm);
});

it('uses deterministic package manager lockfile priority', function (): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();
    $workspace->writeLockfile('package-lock.json');
    $workspace->writeLockfile('yarn.lock');
    $workspace->writeLockfile('pnpm-lock.yaml');

    expect(new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files())->detect())->toBe(FrontendPackageManager::Pnpm);
});

it('builds frontend process commands for the detected package manager', function (): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();
    $workspace->writeLockfile('yarn.lock');

    $factory = new FrontendProcessFactory(
        $workspace->basePath(),
        new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files()),
    );

    expect($factory->install()->command())->toBe(['yarn', 'install'])
        ->and($factory->install()->workingDirectory())->toBe($workspace->basePath())
        ->and($factory->runScript('frontend:check')->command())->toBe(['yarn', 'run', 'frontend:check'])
        ->and($factory->runScript('frontend:check')->workingDirectory())->toBe($workspace->basePath());
});

it('rejects empty frontend script names', function (): void {
    expect(fn(): mixed => FrontendPackageManager::Npm->runScriptCommand(' '))
        ->toThrow(InvalidFrontendCommandException::class);
});

it('registers frontend process services during bootstrap', function (): void {
    $environment = ApplicationTestEnvironment::create();

    $container = Bootstrap::init($environment->basePath())->container();

    expect($container->make(FrontendPackageManagerDetector::class))->toBeInstanceOf(FrontendPackageManagerDetector::class)
        ->and($container->make(FrontendProcessFactory::class))->toBeInstanceOf(FrontendProcessFactory::class);
});
