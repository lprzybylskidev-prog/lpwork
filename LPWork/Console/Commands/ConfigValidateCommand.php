<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Config\EnvironmentConfigurationValidator;
use LPWork\Config\EnvironmentValidationRenderer;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Handles the config validate command console command.
 */
final readonly class ConfigValidateCommand implements Command
{
    /**
     * Creates a new ConfigValidateCommand instance.
     */
    public function __construct(
        private EnvironmentConfigurationValidator $validator,
        private EnvironmentValidationRenderer $renderer = new EnvironmentValidationRenderer(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'config:validate';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Validate required environment configuration values.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $report = $this->validator->validate();

        $this->renderer->render($report, $output);

        return $report->exitCode();
    }
}
