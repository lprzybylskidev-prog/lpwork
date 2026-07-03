<?php

declare(strict_types=1);

use LPWork\Console\Commands\MakeModuleCommand;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\Input;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Console\Output;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\OutputStreams;

afterEach(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('creates a module through the command', function (): void {
    $environment = ApplicationTestEnvironment::create();
    writeMakeModuleCommandAppProvider($environment);
    $streams = OutputStreams::create();
    $command = new MakeModuleCommand(makeModuleCommandCreator($environment->basePath()));

    $exitCode = $command->handle(
        new Input(['lpwork', 'make:module', 'Billing']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('OK Module created.')
        ->toContain($environment->basePath() . '/App/Modules/Billing')
        ->toContain('App\Modules\Billing\BillingServiceProvider')
        ->toContain($environment->basePath() . '/App/AppServiceProvider.php')
        ->and($streams->stderr())->toBe('')
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/BillingServiceProvider.php'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/Assets/AssetsProvider.php'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/resources/frontend/app.ts'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/resources/frontend/app.css'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/tests/backend/BillingModuleTest.php'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/tests/frontend/app.test.ts'))->toBeTrue();
});

it('creates a backend only module through the command', function (): void {
    $environment = ApplicationTestEnvironment::create();
    writeMakeModuleCommandAppProvider($environment);
    $streams = OutputStreams::create();
    $command = new MakeModuleCommand(makeModuleCommandCreator($environment->basePath()));

    $exitCode = $command->handle(
        new Input(['lpwork', 'make:module', 'Billing', '--no-frontend']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stderr())->toBe('')
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/BillingServiceProvider.php'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/Assets/AssetsProvider.php'))->toBeFalse()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/resources/frontend/app.ts'))->toBeFalse()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/tests/backend/BillingModuleTest.php'))->toBeTrue()
        ->and(file_exists($environment->basePath() . '/App/Modules/Billing/tests/frontend/app.test.ts'))->toBeFalse()
        ->and(file_get_contents($environment->basePath() . '/App/Modules/Billing/BillingServiceProvider.php'))->not->toContain('AssetsProvider::class,');
});

it('reports a missing module name', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $streams = OutputStreams::create();
    $command = new MakeModuleCommand(makeModuleCommandCreator($environment->basePath()));

    $exitCode = $command->handle(
        new Input(['lpwork', 'make:module']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain('Missing module name.');
});

it('reports an invalid module name', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $streams = OutputStreams::create();
    $command = new MakeModuleCommand(makeModuleCommandCreator($environment->basePath()));

    $exitCode = $command->handle(
        new Input(['lpwork', 'make:module', 'blog-posts']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain('Invalid module name');
});

function makeModuleCommandCreator(string $basePath): ModuleCreator
{
    $app = new Application($basePath);
    $files = new Filesystem();

    return new ModuleCreator(
        new ModulePathResolver($app),
        $files,
        new ProviderFileRegistrar($app, $files),
        $app,
    );
}

function writeMakeModuleCommandAppProvider(ApplicationTestEnvironment $environment): void
{
    $environment->writeFile('App/AppServiceProvider.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App;

        use LPWork\Foundation\Contracts\ServiceProvider;
        use LPWork\Foundation\Providers\ProviderServiceProvider;

        final class AppServiceProvider extends ProviderServiceProvider
        {
            /**
             * @return list<class-string<ServiceProvider>>
             */
            protected function serviceProviders(): array
            {
                return [
                ];
            }
        }
        PHP);
}
