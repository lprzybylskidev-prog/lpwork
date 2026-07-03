<?php

declare(strict_types=1);

use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\Exceptions\InvalidModuleNameException;
use LPWork\Foundation\Modules\ModulePathResolver;
use Tests\support\ApplicationTestEnvironment;

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('resolves module paths namespaces and provider locations from the application root', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $resolver = new ModulePathResolver(new Application($environment->basePath()));

    $module = $resolver->resolve('Blog');

    expect($module->name())->toBe('Blog')
        ->and($module->path())->toBe($environment->basePath() . '/App/Modules/Blog')
        ->and($module->namespace())->toBe('App\Modules\Blog')
        ->and($module->serviceProviderClass())->toBe('App\Modules\Blog\BlogServiceProvider')
        ->and($module->serviceProviderPath())->toBe($environment->basePath() . '/App/Modules/Blog/BlogServiceProvider.php')
        ->and($module->path('Routes/WebRoutes.php'))->toBe($environment->basePath() . '/App/Modules/Blog/Routes/WebRoutes.php')
        ->and($module->namespace('Routes'))->toBe('App\Modules\Blog\Routes')
        ->and($module->childProviderClass('Routes', 'RoutesProvider'))->toBe('App\Modules\Blog\Routes\RoutesProvider')
        ->and($module->childProviderPath('Routes', 'RoutesProvider'))->toBe($environment->basePath() . '/App/Modules/Blog/Routes/RoutesProvider.php');
});

it('normalizes nested module names without reading the filesystem', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $resolver = new ModulePathResolver(new Application($environment->basePath()));

    $module = $resolver->resolve('admin/blog');

    expect($module->name())->toBe('Blog')
        ->and($module->path())->toBe($environment->basePath() . '/App/Modules/Admin/Blog')
        ->and($module->namespace())->toBe('App\Modules\Admin\Blog')
        ->and($module->serviceProviderClass())->toBe('App\Modules\Admin\Blog\BlogServiceProvider');
});

it('rejects empty and invalid module names explicitly', function (?string $name): void {
    $environment = ApplicationTestEnvironment::create();
    $resolver = new ModulePathResolver(new Application($environment->basePath()));

    expect(fn() => $resolver->resolve($name ?? ''))
        ->toThrow(InvalidModuleNameException::class);
})->with([
    'empty' => [''],
    'spaces' => ['   '],
    'dash' => ['Blog-Admin'],
    'numeric' => ['123Blog'],
    'traversal' => ['../Blog'],
]);

it('supports custom module roots for tooling scenarios', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $resolver = new ModulePathResolver(
        new Application($environment->basePath()),
        rootPath: 'packages/AppModules',
        rootNamespace: 'Packages\AppModules',
    );

    $module = $resolver->resolve('Billing');

    expect($module->path())->toBe($environment->basePath() . '/packages/AppModules/Billing')
        ->and($module->namespace())->toBe('Packages\AppModules\Billing')
        ->and($module->serviceProviderClass())->toBe('Packages\AppModules\Billing\BillingServiceProvider');
});
