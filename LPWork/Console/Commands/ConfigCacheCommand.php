<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Config\ConfigCompiledCache;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Handles the config cache command console command.
 */
final readonly class ConfigCacheCommand implements Command
{
    /**
     * Creates a new ConfigCacheCommand instance.
     */
    public function __construct(
        private ConfigCompiledCache $cache,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'config:cache';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Rebuild the compiled configuration cache.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->rebuild();

        $this->messages->success($output, 'Configuration cache rebuilt successfully.');

        return 0;
    }
}
