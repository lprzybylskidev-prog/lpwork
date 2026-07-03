<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Security\ApplicationKeyGenerator;
use LPWork\Security\EnvironmentApplicationKeyStore;

/**
 * Handles the generate application key command console command.
 */
final readonly class GenerateApplicationKeyCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new GenerateApplicationKeyCommand instance.
     */
    public function __construct(
        private EnvironmentApplicationKeyStore $store,
        private ApplicationKeyGenerator $generator = new ApplicationKeyGenerator(),
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'key:generate';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Generate and store the framework APP_KEY secret.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $force = $input->hasOption('force');

        if ($this->store->current() !== '' && !$force) {
            $this->messages->error($output, 'APP_KEY already has a value. Use --force to overwrite it.');

            return 1;
        }

        $this->store->write($this->generator->generate());
        $this->messages->success($output, 'APP_KEY generated successfully.');

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::flag('force', description: 'Overwrite an existing key and allow generation in production.'),
        ];
    }

    /**
     * @return list<string>
     */
    public function middleware(): array
    {
        return [
            ProductionSafetyMiddleware::class,
        ];
    }

    /**
     * Performs the production safety message operation.
     */
    public function productionSafetyMessage(): string
    {
        return 'Refusing to generate APP_KEY in production without --force.';
    }
}
