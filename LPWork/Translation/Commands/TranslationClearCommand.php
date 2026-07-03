<?php

declare(strict_types=1);

namespace LPWork\Translation\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Translation\TranslationCache;

/**
 * Handles the translation clear command console command.
 */
final readonly class TranslationClearCommand implements Command
{
    /**
     * Creates a new TranslationClearCommand instance.
     */
    public function __construct(
        private TranslationCache $cache,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'translation:clear';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Clear the compiled translation cache.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->clear();

        $this->messages->success($output, 'Translation cache cleared successfully.');

        return 0;
    }
}
