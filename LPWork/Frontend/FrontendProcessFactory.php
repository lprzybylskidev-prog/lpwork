<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Console\ProcessCommand;

/**
 * Creates frontend process factory instances from framework configuration.
 */
final readonly class FrontendProcessFactory
{
    /**
     * Creates a new FrontendProcessFactory instance.
     */
    public function __construct(
        private string $basePath,
        private FrontendPackageManagerDetector $packageManagers,
    ) {}

    /**
     * Performs the install operation.
     */
    public function install(): ProcessCommand
    {
        return $this->command($this->packageManagers->detect()->installCommand());
    }

    /**
     * Runs run script.
     */
    public function runScript(string $script): ProcessCommand
    {
        return $this->command($this->packageManagers->detect()->runScriptCommand($script));
    }

    /**
     * @param non-empty-list<string> $command
     */
    private function command(array $command): ProcessCommand
    {
        return new ProcessCommand($command, $this->basePath);
    }
}
