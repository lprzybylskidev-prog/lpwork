<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Console\CommandRegistry;
use LPWork\Container\Container;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the console health check framework component.
 */
final readonly class ConsoleHealthCheck implements HealthCheck
{
    /**
     * @param list<string> $requiredCommands
     */
    public function __construct(
        private Container $container,
        private array $requiredCommands = [
            'about',
            'cache:clear',
            'cache:rebuild',
            'check',
            'config:cache',
            'config:clear',
            'config:validate',
            'completion:install',
            'coverage',
            'format',
            'health:check',
            'queue:work',
            'route:list',
            'schedule:run',
            'test',
            'test:lpwork',
            'translation:cache',
            'view:clear',
        ],
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'console';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $commands = $this->container->make(CommandRegistry::class);

        if (!$commands instanceof CommandRegistry) {
            return HealthCheckResult::unhealthy($this->name(), 'Command registry resolved to an invalid object.');
        }

        $missing = [];

        foreach ($this->requiredCommands as $command) {
            if ($commands->has($command)) {
                continue;
            }

            $missing[] = $command;
        }

        if ($missing !== []) {
            return HealthCheckResult::unhealthy($this->name(), 'Missing framework console commands: ' . implode(', ', $missing) . '.');
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Console registry contains %d command(s), including required framework commands.', count($commands->all())));
    }
}
