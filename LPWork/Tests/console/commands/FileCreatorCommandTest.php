<?php

declare(strict_types=1);

use LPWork\Console\Commands\FileCreatorCommand;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\Input;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Console\Output;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\FileCreatorTestFactory;
use Tests\support\console\OutputStreams;

afterEach(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('creates a file inside a targeted module from the command option', function (): void {
    $environment = ApplicationTestEnvironment::create();
    createFileCreatorCommandTestModule($environment->basePath(), 'Blog');
    $streams = OutputStreams::create();
    $command = new FileCreatorCommand(
        FileCreatorTestFactory::definition('form-request'),
        FileCreatorTestFactory::creator($environment->basePath()),
    );

    $exitCode = $command->handle(
        new Input(['lpwork', 'make:form-request', 'StorePost', '--module=Blog']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    $path = $environment->basePath() . '/App/Modules/Blog/Validation/Requests/StorePostRequest.php';

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('OK Form request created.')
        ->and($streams->stdout())->toContain('| Path')
        ->and($streams->stdout())->toContain($path)
        ->and($streams->stderr())->toBe('')
        ->and(file_get_contents($path))->toContain('namespace App\Modules\Blog\Validation\Requests;');
});

it('reports an error when a module-owned file is created without a module or path', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $streams = OutputStreams::create();
    $command = new FileCreatorCommand(
        FileCreatorTestFactory::definition('controller'),
        FileCreatorTestFactory::creator($environment->basePath()),
    );

    $exitCode = $command->handle(
        new Input(['lpwork', 'make:controller', 'Dashboard']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain('make:controller requires --module or an explicit --path.');
});

function createFileCreatorCommandTestModule(string $basePath, string $name): void
{
    $app = new Application($basePath);
    $files = new Filesystem();
    $creator = new ModuleCreator(
        new ModulePathResolver($app),
        $files,
        new ProviderFileRegistrar($app, $files),
        $app,
    );

    $creator->create($name, register: false);
}
