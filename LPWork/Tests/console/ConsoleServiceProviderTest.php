<?php

declare(strict_types=1);

use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Config\Config;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Commands\AboutCommand;
use LPWork\Console\Commands\BrowserTaskCommand;
use LPWork\Console\Commands\CacheClearCommand;
use LPWork\Console\Commands\CacheRebuildCommand;
use LPWork\Console\Commands\CompletionGenerateCommand;
use LPWork\Console\Commands\CompletionInstallCommand;
use LPWork\Console\Commands\ConfigCacheCommand;
use LPWork\Console\Commands\ConfigClearCommand;
use LPWork\Console\Commands\ConfigShowCommand;
use LPWork\Console\Commands\ConfigValidateCommand;
use LPWork\Console\Commands\FileCreatorCommand;
use LPWork\Console\Commands\FrontendEntrypointsCommand;
use LPWork\Console\Commands\FrontendTaskCommand;
use LPWork\Console\Commands\MakeModuleCommand;
use LPWork\Console\Commands\ProjectTaskCommand;
use LPWork\Console\Commands\RouteListCommand;
use LPWork\Console\ConsoleMiddlewarePipeline;
use LPWork\Console\ConsoleMiddlewareResolver;
use LPWork\Console\ConsoleMiddlewareStack;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Console\Providers\ConsoleServiceProvider;
use LPWork\Container\Container;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Responses\ConsoleResponse;
use LPWork\Storage\Providers\StorageServiceProvider;
use Tests\support\config\ApplicationConfigDefinitions;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('defines the console service provider', function (): void {
    $provider = new ConsoleServiceProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('coordinates focused console service registrars', function (): void {
    $provider = file_get_contents(\Tests\support\ProjectPaths::root() . '/LPWork/Console/Providers/ConsoleServiceProvider.php');

    if ($provider === false) {
        throw new RuntimeException('Could not read console service provider.');
    }

    expect($provider)
        ->toContain('CoreConsoleServicesRegistrar')
        ->toContain('ConsoleCacheConfigRegistrar')
        ->toContain('ConsoleFrontendTaskRegistrar')
        ->toContain('ConsoleGeneratorRegistrar');
});

it('registers the command registry as a singleton', function (): void {
    $container = new Container();
    $app = new Application(\Tests\support\ProjectPaths::root());
    new FoundationServiceProvider($app)->register($container);
    ApplicationConfigDefinitions::initStorageAndCache();
    ApplicationConfigDefinitions::registerStorageAndCacheSource($container);

    new StorageServiceProvider()->register($container);
    new CacheServiceProvider()->register($container);
    $provider = new ConsoleServiceProvider();

    $provider->register($container);

    expect($container->make(CommandRegistry::class))
        ->toBeInstanceOf(CommandRegistry::class)
        ->toBe($container->make(CommandRegistry::class));

    $commands = $container->make(CommandRegistry::class);

    expect($commands)->toBeInstanceOf(CommandRegistry::class);

    if ($commands instanceof CommandRegistry) {
        expect($commands->get('about'))->toBeInstanceOf(AboutCommand::class)
            ->and($commands->get('browser:install'))->toBeInstanceOf(BrowserTaskCommand::class)
            ->and($commands->get('browser:test'))->toBeInstanceOf(BrowserTaskCommand::class)
            ->and($commands->get('browser:ui'))->toBeInstanceOf(BrowserTaskCommand::class)
            ->and($commands->get('cache:clear'))->toBeInstanceOf(CacheClearCommand::class)
            ->and($commands->get('cache:rebuild'))->toBeInstanceOf(CacheRebuildCommand::class)
            ->and($commands->get('check'))->toBeInstanceOf(ProjectTaskCommand::class)
            ->and($commands->get('completion:generate'))->toBeInstanceOf(CompletionGenerateCommand::class)
            ->and($commands->get('completion:install'))->toBeInstanceOf(CompletionInstallCommand::class)
            ->and($commands->get('config:cache'))->toBeInstanceOf(ConfigCacheCommand::class)
            ->and($commands->get('config:clear'))->toBeInstanceOf(ConfigClearCommand::class)
            ->and($commands->get('config:show'))->toBeInstanceOf(ConfigShowCommand::class)
            ->and($commands->get('config:validate'))->toBeInstanceOf(ConfigValidateCommand::class)
            ->and($commands->get('coverage'))->toBeInstanceOf(ProjectTaskCommand::class)
            ->and($commands->get('format'))->toBeInstanceOf(ProjectTaskCommand::class)
            ->and($commands->get('frontend:entries'))->toBeInstanceOf(FrontendEntrypointsCommand::class)
            ->and($commands->get('frontend:build'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('frontend:check'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('frontend:clean'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('frontend:dev'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('frontend:format'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('frontend:install'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('frontend:test'))->toBeInstanceOf(FrontendTaskCommand::class)
            ->and($commands->get('make:broadcast-event'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:console-middleware'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:command'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:form-request'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:health-check'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:job'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:module'))->toBeInstanceOf(MakeModuleCommand::class)
            ->and($commands->get('make:route-definition'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:view-engine'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('make:view-extension'))->toBeInstanceOf(FileCreatorCommand::class)
            ->and($commands->get('route:list'))->toBeInstanceOf(RouteListCommand::class)
            ->and($commands->get('test'))->toBeInstanceOf(ProjectTaskCommand::class)
            ->and($commands->get('test:lpwork'))->toBeInstanceOf(ProjectTaskCommand::class);
    }

    expect($container->make(ConsoleMiddlewareStack::class))
        ->toBeInstanceOf(ConsoleMiddlewareStack::class)
        ->toBe($container->make(ConsoleMiddlewareStack::class))
        ->and($container->make(ConsoleMiddlewareResolver::class))
        ->toBeInstanceOf(ConsoleMiddlewareResolver::class)
        ->toBe($container->make(ConsoleMiddlewareResolver::class));
});

it('uses the shared runtime environment for production-sensitive middleware', function (): void {
    $container = new Container();
    $app = new Application(\Tests\support\ProjectPaths::root());
    $container->instance(Application::class, $app);
    $container->instance(RuntimeEnvironment::class, new RuntimeEnvironment('staging', ['production', 'staging']));

    new ConsoleServiceProvider()->register($container);

    $resolver = $container->make(ConsoleMiddlewareResolver::class);

    expect($resolver)->toBeInstanceOf(ConsoleMiddlewareResolver::class);

    if (!$resolver instanceof ConsoleMiddlewareResolver) {
        return;
    }

    $command = new class implements Command, HasConsoleMiddleware, ProductionSensitiveCommand {
        public function name(): string
        {
            return 'danger:test';
        }

        public function description(): string
        {
            return 'Danger test command.';
        }

        public function handle(Input $input, Output $output): int
        {
            $output->writeln('handled');

            return 0;
        }

        public function middleware(): array
        {
            return [ProductionSafetyMiddleware::class];
        }

        public function productionSafetyMessage(): string
        {
            return "blocked\n";
        }
    };

    $response = new ConsoleMiddlewarePipeline($resolver->resolve($command))
        ->handle(
            new Input(['lpwork', 'danger:test']),
            static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                static fn(Output $output): int => $command->handle($input, $output),
            ),
        );

    $streams = OutputStreams::create();

    expect($response->send(new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toBe("blocked\n");
});
