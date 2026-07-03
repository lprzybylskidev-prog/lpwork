<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Config\ConfigCache;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Handles the config clear command console command.
 */
final readonly class ConfigClearCommand implements Command
{
    /**
     * Creates a new ConfigClearCommand instance.
     */
    public function __construct(
        private ConfigCache $cache,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'config:clear';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Clear the compiled configuration cache.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->clear();

        $this->messages->success($output, 'Configuration cache cleared successfully.');

        return 0;
    }
}
