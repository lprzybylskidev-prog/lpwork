<?php

declare(strict_types=1);

namespace LPWork\Routing\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Routing\RouteCache;

/**
 * Handles the route clear command console command.
 */
final readonly class RouteClearCommand implements Command
{
    /**
     * Creates a new RouteClearCommand instance.
     */
    public function __construct(
        private RouteCache $cache,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'route:clear';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Clear the compiled route cache.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->clear();

        $this->messages->success($output, 'Route cache cleared successfully.');

        return 0;
    }
}
