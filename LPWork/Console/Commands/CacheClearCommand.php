<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use function implode;

use LPWork\Cache\CacheClearer;
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

/**
 * Handles the cache clear command console command.
 */
final readonly class CacheClearCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new CacheClearCommand instance.
     */
    public function __construct(
        private CacheClearer $clearer,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'cache:clear';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Clear framework cache stores.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $cleared = $this->clearer->clear($input->argument(0));

        $this->messages->success($output, 'Cache cleared: ' . implode(', ', $cleared) . '.');

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::optional('target', 'Cache target to clear, such as framework or views.'),
        ];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::flag('force', description: 'Allow clearing cache in production.'),
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
        return 'Refusing to clear cache in production without --force.';
    }
}
