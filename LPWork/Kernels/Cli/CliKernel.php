<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli;

use LPWork\Console\CommandHelpRenderer;
use LPWork\Console\CommandListRenderer;
use LPWork\Console\CommandRegistry;
use LPWork\Console\ConsoleBootstrapNotice;
use LPWork\Console\ConsoleMiddlewarePipeline;
use LPWork\Console\ConsoleMiddlewareResolver;
use LPWork\Console\ConsoleMiddlewareStack;
use LPWork\Console\Contracts\Command;
use LPWork\Console\GlobalHelpRenderer;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Renderers\CliExceptionRenderer;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\Application;
use LPWork\Kernels\AbstractKernel;
use LPWork\Kernels\Cli\Contracts\CliKernel as ContractsCliKernel;
use LPWork\Kernels\Cli\Events\CliCommandFailed;
use LPWork\Kernels\Cli\Events\CliCommandHandled;
use LPWork\Requests\ConsoleRequest;
use LPWork\Responses\ConsoleResponse;
use LPWork\Throttle\CliThrottle;
use Throwable;

/**
 * Represents the cli kernel framework component.
 */
final class CliKernel extends AbstractKernel implements ContractsCliKernel
{
    private ?CliExceptionHandler $exceptionHandler = null;

    private readonly ?CliThrottle $cliThrottle;

    private readonly ConsoleMiddlewareResolver $middlewareResolver;

    private readonly ?EventDispatcher $events;

    /**
     * Creates a new CliKernel instance.
     */
    public function __construct(
        Application $app,
        private readonly ?Emitter $emitter = null,
    ) {
        parent::__construct($app);

        $this->cliThrottle = $this->resolveCliThrottle();
        $this->middlewareResolver = $this->resolveMiddlewareResolver();
        $this->events = $this->resolveEventDispatcher();
    }

    /**
     * Registers or stores bootstrap.
     */
    public function bootstrap(): void
    {
        $this->registerErrorHandler();
        $handler = $this->cliExceptionHandler();

        $handler->register();
        $this->exceptionHandler = $handler;
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(array $argv): int
    {
        $emitter = $this->emitter ?? $this->consoleEmitter();
        $request = ConsoleRequest::fromArgv($argv);
        $started = hrtime(true);

        try {
            $exitCode = $emitter->emit($this->response($request));
            $this->dispatchHandledCommand($request, $exitCode, $started);

            return $exitCode;
        } catch (Throwable $throwable) {
            $exitCode = $emitter->emit($this->exceptionResponse($throwable));
            $this->dispatchFailedCommand($request, $exitCode, $throwable, $started);

            return $exitCode;
        }
    }

    private function response(ConsoleRequest $request): ConsoleResponse
    {
        $throttled = $this->cliThrottle?->response($request);

        if ($throttled !== null) {
            return $throttled;
        }

        $input = $request->input();
        $commands = $this->commands();

        if ($this->isGlobalHelpRequest($input)) {
            return ConsoleResponse::using(fn(Output $output): int => $this->globalHelpResponse($output));
        }

        if (!$input->hasCommand()) {
            $renderer = $this->commandListRenderer();

            return ConsoleResponse::using(static function (Output $output) use ($commands, $renderer): int {
                $renderer->render($commands, $output);

                return 0;
            });
        }

        $command = $input->command();

        if ($command === null || !$commands->has($command)) {
            $notice = $this->consoleBootstrapNotice();

            if ($notice !== null) {
                return ConsoleResponse::output(
                    stderr: $notice->message . PHP_EOL
                        . 'Only config:clear and config:cache are available until the configuration cache is rebuilt.'
                        . PHP_EOL
                        . sprintf('Command not available while the configuration cache is invalid: %s', $command ?? ''),
                    exitCode: 1,
                );
            }

            return ConsoleResponse::output(stderr: sprintf('Command not found: %s', $command ?? ''), exitCode: 1);
        }

        $resolvedCommand = $commands->get($command);

        if ($input->hasOption('help') || $input->hasOption('h')) {
            return ConsoleResponse::using(fn(Output $output): int => $this->helpResponse($resolvedCommand, $output));
        }

        return $this->commandResponse($resolvedCommand, $input);
    }

    private function helpResponse(Command $command, Output $output): int
    {
        $this->commandHelpRenderer()->render($command, $output);

        return 0;
    }

    private function globalHelpResponse(Output $output): int
    {
        $this->globalHelpRenderer()->render($output);

        return 0;
    }

    private function commandResponse(Command $command, Input $input): ConsoleResponse
    {
        return new ConsoleMiddlewarePipeline($this->middlewareResolver->resolve($command))
            ->handle(
                $input,
                static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                    static fn(Output $output): int => $command->handle($input, $output),
                ),
            );
    }

    private function exceptionResponse(Throwable $throwable): ConsoleResponse
    {
        if ($this->exceptionHandler !== null) {
            return ConsoleResponse::output(stderr: $this->exceptionHandler->handle($throwable), exitCode: 1);
        }

        $renderer = $this->containerObject(CliExceptionRenderer::class);
        $renderer = $renderer instanceof CliExceptionRenderer ? $renderer : new CliExceptionRenderer();

        return ConsoleResponse::output(stderr: $renderer->render($throwable), exitCode: 1);
    }

    private function commands(): CommandRegistry
    {
        $commands = $this->app->container()->make(CommandRegistry::class);

        if ($commands instanceof CommandRegistry) {
            return $commands;
        }

        return new CommandRegistry();
    }

    private function consoleEmitter(): ConsoleEmitter
    {
        $emitter = $this->containerObject(ConsoleEmitter::class);

        if ($emitter instanceof ConsoleEmitter) {
            return $emitter;
        }

        return ConsoleEmitter::terminal();
    }

    private function commandListRenderer(): CommandListRenderer
    {
        $renderer = $this->containerObject(CommandListRenderer::class);

        if ($renderer instanceof CommandListRenderer) {
            return $renderer;
        }

        return new CommandListRenderer();
    }

    private function commandHelpRenderer(): CommandHelpRenderer
    {
        $renderer = $this->containerObject(CommandHelpRenderer::class);

        if ($renderer instanceof CommandHelpRenderer) {
            return $renderer;
        }

        return new CommandHelpRenderer();
    }

    private function globalHelpRenderer(): GlobalHelpRenderer
    {
        $renderer = $this->containerObject(GlobalHelpRenderer::class);

        if ($renderer instanceof GlobalHelpRenderer) {
            return $renderer;
        }

        return new GlobalHelpRenderer();
    }

    private function consoleBootstrapNotice(): ?ConsoleBootstrapNotice
    {
        $notice = $this->containerObject(ConsoleBootstrapNotice::class);

        return $notice instanceof ConsoleBootstrapNotice ? $notice : null;
    }

    private function isGlobalHelpRequest(Input $input): bool
    {
        return $input->command() === '--help' || $input->command() === '-h';
    }

    private function resolveCliThrottle(): ?CliThrottle
    {
        $throttle = $this->containerObject(CliThrottle::class);

        if ($throttle instanceof CliThrottle) {
            return $throttle;
        }

        return null;
    }

    private function resolveMiddlewareResolver(): ConsoleMiddlewareResolver
    {
        $resolver = $this->containerObject(ConsoleMiddlewareResolver::class);

        if ($resolver instanceof ConsoleMiddlewareResolver) {
            return $resolver;
        }

        $stack = $this->containerObject(ConsoleMiddlewareStack::class);

        if (!$stack instanceof ConsoleMiddlewareStack) {
            $stack = new ConsoleMiddlewareStack();
        }

        return new ConsoleMiddlewareResolver($this->app, $stack);
    }

    private function resolveEventDispatcher(): ?EventDispatcher
    {
        $dispatcher = $this->containerObject(EventDispatcher::class);

        return $dispatcher instanceof EventDispatcher ? $dispatcher : null;
    }

    private function dispatchHandledCommand(ConsoleRequest $request, int $exitCode, int $started): void
    {
        $this->events?->dispatch(new CliCommandHandled(
            request: $request,
            exitCode: $exitCode,
            durationMs: $this->durationMs($started),
        ));
    }

    private function dispatchFailedCommand(ConsoleRequest $request, int $exitCode, Throwable $throwable, int $started): void
    {
        $this->events?->dispatch(new CliCommandFailed(
            request: $request,
            exitCode: $exitCode,
            durationMs: $this->durationMs($started),
            throwable: $throwable,
        ));
    }

    private function durationMs(int $started): float
    {
        return round((hrtime(true) - $started) / 1_000_000, 3);
    }
}
