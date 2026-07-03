<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use function array_map;

use LPWork\Console\CommandRegistry;
use LPWork\Console\Completion\Contracts\CompletionScriptRenderer;
use LPWork\Console\Exceptions\UnsupportedCompletionShellException;

/**
 * Represents the completion script generator framework component.
 */
final readonly class CompletionScriptGenerator
{
    /**
     * @param list<CompletionScriptRenderer> $renderers
     */
    public function __construct(
        private CompletionDefinitionFactory $definitions = new CompletionDefinitionFactory(),
        private array $renderers = [],
    ) {}

    public static function default(): self
    {
        return new self(new CompletionDefinitionFactory(), [
            new BashCompletionScriptRenderer(),
            new ZshCompletionScriptRenderer(),
            new FishCompletionScriptRenderer(),
        ]);
    }

    /**
     * Builds or returns generate.
     */
    public function generate(string $shell, CommandRegistry $registry): string
    {
        return $this->renderer($shell)->render($this->definitions->create($registry));
    }

    private function renderer(string $shell): CompletionScriptRenderer
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->shell() === $shell) {
                return $renderer;
            }
        }

        throw UnsupportedCompletionShellException::forShell($shell, $this->supportedShells());
    }

    /**
     * @return list<string>
     */
    private function supportedShells(): array
    {
        return array_map(
            static fn(CompletionScriptRenderer $renderer): string => $renderer->shell(),
            $this->renderers,
        );
    }
}
