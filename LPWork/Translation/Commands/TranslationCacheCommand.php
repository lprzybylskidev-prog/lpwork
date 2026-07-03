<?php

declare(strict_types=1);

namespace LPWork\Translation\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Translation\TranslationCompiledCache;

/**
 * Handles the translation cache command console command.
 */
final readonly class TranslationCacheCommand implements Command
{
    /**
     * Creates a new TranslationCacheCommand instance.
     */
    public function __construct(
        private TranslationCompiledCache $cache,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'translation:cache';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Compile translation files into a cache file.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->cache->rebuild();

        $this->messages->success($output, 'Translation cache rebuilt successfully.');

        return 0;
    }
}
