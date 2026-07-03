<?php

declare(strict_types=1);

namespace Tests\support\testing\Cli;

use LPWork\Console\CommandRegistry;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Output;
use LPWork\Console\Questioner;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Foundation\Application;
use LPWork\Kernels\Cli\CliKernel;
use Tests\support\console\OutputStreams;

final class CliTestClient
{
    /**
     * @var list<class-string<Command>>
     */
    private array $commands = [];

    private string $input = '';

    public function __construct(
        private readonly Application $app,
    ) {}

    public static function forApplication(Application $app): self
    {
        return new self($app);
    }

    public function withInput(string $input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param class-string<Command> $command
     */
    public function withCommand(string $command): self
    {
        $this->commands[] = $command;

        return $this;
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): TestConsoleResult
    {
        $streams = OutputStreams::create($this->input);
        $output = new Output($streams->stdout, $streams->stderr, decorated: false);

        $this->app->container()->instance(Output::class, $output);
        $this->app->container()->instance(Questioner::class, new Questioner($output, $streams->stdin));
        $this->registerCommands();

        $exitCode = new CliKernel($this->app, new ConsoleEmitter($output))->handle($argv);

        return new TestConsoleResult(
            exitCode: $exitCode,
            stdout: $streams->stdout(),
            stderr: $streams->stderr(),
        );
    }

    public function command(string $command, string ...$arguments): TestConsoleResult
    {
        return $this->run(array_values(['lpwork', $command, ...$arguments]));
    }

    private function registerCommands(): void
    {
        if ($this->commands === []) {
            return;
        }

        $registry = $this->commandRegistry();

        foreach ($this->commands as $command) {
            $resolved = $this->app->container()->make($command);

            if ($resolved instanceof Command) {
                $registry->add($resolved);
            }
        }
    }

    private function commandRegistry(): CommandRegistry
    {
        $registry = $this->app->container()->make(CommandRegistry::class);

        if ($registry instanceof CommandRegistry) {
            $this->app->container()->instance(CommandRegistry::class, $registry);

            return $registry;
        }

        $registry = new CommandRegistry();
        $this->app->container()->instance(CommandRegistry::class, $registry);

        return $registry;
    }
}
