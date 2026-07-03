<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use JsonException;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\HiddenCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Frontend\ViteEntrypointResolver;

/**
 * Handles the frontend entrypoints command console command.
 */
final readonly class FrontendEntrypointsCommand implements Command, HiddenCommand
{
    /**
     * Creates a new FrontendEntrypointsCommand instance.
     */
    public function __construct(
        private ViteEntrypointResolver $entries,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'frontend:entries';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Render declared frontend entrypoints for Vite.';
    }

    /**
     * @throws JsonException
     */
    public function handle(Input $input, Output $output): int
    {
        $output->writeln(json_encode($this->entries->buildInputs(), JSON_THROW_ON_ERROR));

        return 0;
    }
}
