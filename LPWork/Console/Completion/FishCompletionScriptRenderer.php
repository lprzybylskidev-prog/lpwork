<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use function implode;

use LPWork\Console\Completion\Contracts\CompletionScriptRenderer;

use function sprintf;

/**
 * Renders fish completion script renderer output.
 */
final readonly class FishCompletionScriptRenderer implements CompletionScriptRenderer
{
    /**
     * Creates a new FishCompletionScriptRenderer instance.
     */
    public function __construct(
        private ShellCompletionEscaper $escaper = new ShellCompletionEscaper(),
    ) {}

    /**
     * Performs the shell operation.
     */
    public function shell(): string
    {
        return 'fish';
    }

    /**
     * Renders this component into its output representation.
     */
    public function render(CompletionDefinition $definition): string
    {
        $lines = [
            '# LPWork shell completion for fish.',
        ];

        foreach ($definition->commands() as $command) {
            $this->appendArgumentComment($lines, $command);
            $lines[] = sprintf(
                'complete -c %s -f -n %s -a %s -d %s',
                $definition->program(),
                $this->escaper->quote('__fish_use_subcommand'),
                $this->escaper->quote($command->name()),
                $this->escaper->quote($command->description()),
            );

            foreach ($command->options() as $option) {
                $line = sprintf(
                    'complete -c %s -f -n %s -l %s -d %s',
                    $definition->program(),
                    $this->escaper->quote('__fish_seen_subcommand_from ' . $command->name()),
                    $option->name(),
                    $this->escaper->quote($option->description()),
                );

                if ($option->shortcut() !== null) {
                    $line .= ' -s ' . $option->shortcut();
                }

                $lines[] = $line;
            }
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $lines
     */
    private function appendArgumentComment(array &$lines, CompletionCommand $command): void
    {
        if ($command->arguments() === []) {
            return;
        }

        $lines[] = '# ' . $command->name() . ' arguments: ' . implode(' ', array_map(
            static fn(CompletionArgument $argument): string => $argument->name(),
            $command->arguments(),
        ));
    }
}
