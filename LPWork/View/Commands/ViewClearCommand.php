<?php

declare(strict_types=1);

namespace LPWork\View\Commands;

use LPWork\Cache\CacheManager;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Handles the view clear command console command.
 */
final readonly class ViewClearCommand implements Command
{
    /**
     * Creates a new ViewClearCommand instance.
     */
    public function __construct(
        private CacheManager $cache,
        private string $cacheStore,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'view:clear';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Clear cached view lookup data.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->store($this->cacheStore)->clear();
        $this->messages->success($output, sprintf('View cache cleared: %s.', $this->cacheStore));

        return 0;
    }
}
