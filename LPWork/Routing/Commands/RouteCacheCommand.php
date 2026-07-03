<?php

declare(strict_types=1);

namespace LPWork\Routing\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Routing\RouteCompiledCache;

/**
 * Handles the route cache command console command.
 */
final readonly class RouteCacheCommand implements Command
{
    /**
     * Creates a new RouteCacheCommand instance.
     */
    public function __construct(
        private RouteCompiledCache $cache,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'route:cache';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Compile application routes into a cache file.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->rebuild();

        $this->messages->success($output, 'Route cache rebuilt successfully.');

        return 0;
    }
}
