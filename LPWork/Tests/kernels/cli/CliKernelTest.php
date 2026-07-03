<?php

declare(strict_types=1);

use LPWork\Bootstrap\Bootstrap;
use LPWork\Config\Config;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Commands\CompletionGenerateCommand;
use LPWork\Console\Commands\FileCreatorCommand;
use LPWork\Console\Completion\CompletionScriptGenerator;
use LPWork\Console\ConsoleMiddlewareStack;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Console\Output;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\Environment\Environment;
use LPWork\Events\Providers\EventServiceProvider;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use LPWork\Kernels\Cli\CliKernel;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogChannel;
use LPWork\Responses\ConsoleResponse;
use LPWork\Responses\Contracts\Response;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleLimiter;
use Tests\support\ApplicationFactory;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\DescribedCommand;
use Tests\support\console\FileCreatorTestFactory;
use Tests\support\console\FirstConsoleMiddleware;
use Tests\support\console\MiddlewareCommand;
use Tests\support\console\OutputStreams;
use Tests\support\console\TestCommand;
use Tests\support\testing\Logging\TestLogDriver;
use Tests\support\throttle\MutableThrottleClock;
use Tests\support\throttle\ThrottleConfigBuilder;

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

it('lists commands when no command is provided', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new TestCommand('preview', 'Preview command.'));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork']))->toBe(0)
        ->and($streams->stdout())->toContain('LPWork')
        ->and($streams->stdout())->toContain('Usage:')
        ->and($streams->stdout())->toContain('lpwork <command> [arguments] [options]')
        ->and($streams->stdout())->toContain('Available commands:')
        ->and($streams->stdout())->toContain('preview')
        ->and($streams->stderr())->toBe('');
});

it('logs handled CLI commands with exit code and duration context', function (): void {
    $streams = OutputStreams::create();
    $driver = new TestLogDriver();
    $app = ApplicationFactory::create();
    $app->container()->instance(Logger::class, new LogChannel('app', $driver));
    $app->register(new EventServiceProvider());
    $commands = new CommandRegistry();
    $commands->add(new TestCommand('preview'));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    $kernel->handle(['lpwork', 'preview']);
    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Info)
        ->and($record->message)->toBe('CLI command handled.')
        ->and($record->context['command'])->toBe('preview')
        ->and($record->context['exit_code'])->toBe(0)
        ->and($record->context['duration_ms'])->toBeFloat();
});

it('returns an error code when command is unknown', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $app->container()->instance(ConsoleEmitter::class, new ConsoleEmitter(new Output($streams->stdout, $streams->stderr)));

    $kernel = new CliKernel($app);

    expect($kernel->handle(['lpwork', 'missing']))->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toBe("Command not found: missing\n");
});

it('renders per-command help without running the command', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new DescribedCommand());
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'users:import', '--help']))->toBe(0)
        ->and($streams->stdout())->toContain('lpwork users:import <file> [mode] [options]')
        ->and($streams->stdout())->not->toContain('imported')
        ->and($streams->stderr())->toBe('');
});

it('renders global help when the help flag is used without a command', function (string $flag): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new TestCommand('preview', 'Preview command.'));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', $flag]))->toBe(0)
        ->and($streams->stdout())->toContain('LPWork Console Help')
        ->and($streams->stdout())->toContain('Global Options:')
        ->and($streams->stdout())->toContain('Common Workflows:')
        ->and($streams->stdout())->not->toContain('Command not found')
        ->and($streams->stderr())->toBe('');
})->with(['--help', '-h']);

it('generates shell completion through the CLI boundary', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new DescribedCommand());
    $commands->add(new CompletionGenerateCommand($commands, CompletionScriptGenerator::default()));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'completion:generate', 'bash']))->toBe(0)
        ->and($streams->stdout())->toContain('# LPWork shell completion for bash.')
        ->and($streams->stdout())->toContain('users:import')
        ->and($streams->stdout())->toContain('--force')
        ->and($streams->stderr())->toBe('');
});

it('passes a stateless module context from global CLI options to make commands', function (): void {
    $environment = ApplicationTestEnvironment::create();
    createCliKernelModuleContextTestModule($environment->basePath(), 'Blog');
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create($environment->basePath());
    $commands = new CommandRegistry();
    $commands->add(new FileCreatorCommand(
        FileCreatorTestFactory::definition('form-request'),
        FileCreatorTestFactory::creator($environment->basePath()),
    ));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    $path = $environment->basePath() . '/App/Modules/Blog/Validation/Requests/StorePostRequest.php';

    expect($kernel->handle(['lpwork', '--module=Blog', 'make:form-request', 'StorePost']))->toBe(0)
        ->and($streams->stdout())->toContain('OK Form request created.')
        ->toContain($path)
        ->toContain('Blog')
        ->and($streams->stderr())->toBe('')
        ->and(file_get_contents($path))->toContain('namespace App\Modules\Blog\Validation\Requests;');
});

it('returns an error for unsupported completion shells through the CLI boundary', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new CompletionGenerateCommand($commands, CompletionScriptGenerator::default()));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'completion:generate', 'powershell']))->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain('Unsupported shell [powershell]. Supported shells: bash, zsh, fish.');
});

it('returns an error code when console flow throws', function (): void {
    $streams = OutputStreams::create();

    $emitter = new class (new Output($streams->stdout, $streams->stderr)) implements Emitter {
        public int $calls = 0;

        public function __construct(
            private readonly Output $output,
        ) {}

        public function emit(Response $response): int
        {
            $this->calls++;

            if ($this->calls === 1) {
                throw new RuntimeException('Emit failed');
            }

            if (!$response instanceof ConsoleResponse) {
                throw new RuntimeException(sprintf('Expected %s.', ConsoleResponse::class));
            }

            return $response->send($this->output);
        }
    };

    $kernel = new CliKernel(ApplicationFactory::create(), $emitter);

    expect($kernel->handle(['lpwork']))->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain(RuntimeException::class)
        ->and($streams->stderr())->toContain('Emit failed');
});

it('applies CLI throttle before running commands', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new TestCommand('preview', 'Preview command.'));
    $app->container()->instance(CommandRegistry::class, $commands);
    $app->container()->instance(ThrottleConfig::class, ThrottleConfigBuilder::config(cli: true, maxAttempts: 1));
    $app->container()->instance(ThrottleLimiter::class, new ThrottleLimiter(
        new InMemoryThrottleStorage(),
        new MutableThrottleClock(),
    ));

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'preview']))->toBe(0)
        ->and($kernel->handle(['lpwork', 'preview']))->toBe(1)
        ->and($streams->stderr())->toContain('Too many CLI attempts. Retry after 60 seconds.');
});

it('passes commands through global and per-command CLI middleware', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $commands = new CommandRegistry();
    $commands->add(new MiddlewareCommand());
    $stack = new ConsoleMiddlewareStack();
    $stack->add(FirstConsoleMiddleware::class);
    $app->container()->instance(CommandRegistry::class, $commands);
    $app->container()->instance(ConsoleMiddlewareStack::class, $stack);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'middleware']))->toBe(0)
        ->and($streams->stdout())->toBe("first-before\nsecond-before\ncommand\nsecond-after\nfirst-after\n")
        ->and($streams->stderr())->toBe('');
});

it('does not run command middleware for unknown commands', function (): void {
    $streams = OutputStreams::create();
    $app = ApplicationFactory::create();
    $stack = new ConsoleMiddlewareStack();
    $stack->add(FirstConsoleMiddleware::class);
    $app->container()->instance(ConsoleMiddlewareStack::class, $stack);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'missing']))->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toBe("Command not found: missing\n");
});

it('uses env-backed CLI throttle separately from HTTP security config', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('SECURITY_CSRF_ENABLED', true);
    $environment->setEnvValue('SECURITY_ENFORCE_HTTPS', true);
    $environment->setEnvValue('THROTTLE_HTTP_WEB_ENABLED', true);
    $environment->setEnvValue('THROTTLE_HTTP_API_ENABLED', true);
    $environment->setEnvValue('THROTTLE_CLI_ENABLED', true);
    $environment->setEnvValue('THROTTLE_CLI_MAX_ATTEMPTS', 1);
    $environment->setEnvValue('THROTTLE_CLI_DECAY_SECONDS', 60);

    $streams = OutputStreams::create();
    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'preview']);
    $commands = new CommandRegistry();
    $commands->add(new TestCommand('preview'));
    $app->container()->instance(CommandRegistry::class, $commands);

    $kernel = new CliKernel(
        $app,
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    );

    expect($kernel->handle(['lpwork', 'preview']))->toBe(0)
        ->and($kernel->handle(['lpwork', 'preview']))->toBe(1)
        ->and($streams->stderr())->toContain('Too many CLI attempts. Retry after 60 seconds.');
});

function createCliKernelModuleContextTestModule(string $basePath, string $name): void
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
