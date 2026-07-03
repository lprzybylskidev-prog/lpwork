<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use function implode;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Foundation\Contracts\CompiledCache;

/**
 * Handles the cache rebuild command console command.
 */
final readonly class CacheRebuildCommand implements Command, DescribesInput
{
    /**
     * Creates a new CacheRebuildCommand instance.
     */
    public function __construct(
        private CompiledCacheRegistry $caches,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'cache:rebuild';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Rebuild all compiled framework caches.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $selectedCaches = $this->selectedCaches($input);

        if ($selectedCaches === []) {
            $this->messages->error($output, 'The --only option must be one of: ' . implode(', ', $this->caches->names()) . '.');

            return 1;
        }

        $this->messages->section($output, 'Rebuilding framework caches:');

        foreach ($selectedCaches as $cache) {
            $cache->rebuild();
            $this->messages->success($output, $cache->label() . ' rebuilt successfully.');
        }

        $this->messages->success($output, 'Framework caches rebuilt.');
        $this->messages->summary($output, $this->summary($selectedCaches));

        return 0;
    }

    /**
     * Performs the arguments operation.
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * Returns options.
     */
    public function options(): array
    {
        return [
            ConsoleOption::multiple('only', description: 'Only rebuild selected caches: config, routes, translations.'),
        ];
    }

    /**
     * @return list<CompiledCache>
     */
    private function selectedCaches(Input $input): array
    {
        $only = $input->optionValues('only');

        if ($only === []) {
            return array_values($this->caches->all());
        }

        $selected = [];

        foreach ($only as $value) {
            if (!is_string($value)) {
                return [];
            }

            $cache = $this->caches->find($value);

            if (!$cache instanceof CompiledCache) {
                return [];
            }

            $selected[$cache->name()] = $cache;
        }

        return array_values($selected);
    }

    /**
     * @param list<CompiledCache> $selectedCaches
     *
     * @return array<string, string>
     */
    private function summary(array $selectedCaches): array
    {
        $summary = [];

        foreach ($selectedCaches as $cache) {
            $summary[$cache->name()] = $cache->label();
        }

        return $summary;
    }
}
