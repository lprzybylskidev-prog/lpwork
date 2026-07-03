<?php

declare(strict_types=1);

namespace LPWork\Console\Completion\Contracts;

use LPWork\Console\Completion\CompletionDefinition;

/**
 * Defines the contract for completion script renderer.
 */
interface CompletionScriptRenderer
{
    /**
     * Performs the shell operation.
     */
    public function shell(): string;

    /**
     * Renders this component into its output representation.
     */
    public function render(CompletionDefinition $definition): string;
}
