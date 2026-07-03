<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Config\Config;
use LPWork\Config\ConfigShowRenderer;
use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Handles the config show command console command.
 */
final readonly class ConfigShowCommand implements Command, DescribesInput
{
    /**
     * Creates a new ConfigShowCommand instance.
     */
    public function __construct(
        private ConfigShowRenderer $renderer = new ConfigShowRenderer(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'config:show';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Display loaded configuration values.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $key = $input->argument(0);
        $config = $key === null ? Config::all() : [$key => Config::get($key)];

        $this->renderer->render($config, $output, showSecrets: $input->hasOption('show-secrets'));

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::optional('key', 'Configuration key to display.'),
        ];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::flag('show-secrets', null, 'Display sensitive configuration values.'),
        ];
    }
}
