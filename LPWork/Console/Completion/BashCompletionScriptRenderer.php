<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use function array_map;
use function implode;

use LPWork\Console\Completion\Contracts\CompletionScriptRenderer;

use function sprintf;

/**
 * Renders bash completion script renderer output.
 */
final readonly class BashCompletionScriptRenderer implements CompletionScriptRenderer
{
    /**
     * Creates a new BashCompletionScriptRenderer instance.
     */
    public function __construct(
        private ShellCompletionEscaper $escaper = new ShellCompletionEscaper(),
    ) {}

    /**
     * Performs the shell operation.
     */
    public function shell(): string
    {
        return 'bash';
    }

    /**
     * Renders this component into its output representation.
     */
    public function render(CompletionDefinition $definition): string
    {
        $lines = [
            '# LPWork shell completion for bash.',
            sprintf('_%s_completion() {', $definition->program()),
            '    local current command line words',
            '    line="${COMP_LINE:0:COMP_POINT}"',
            '    current="${line##* }"',
            sprintf('    words="${line#%s }"', $definition->program()),
            '    command="${words%% *}"',
            '',
            '    if [[ "$words" == "$current" ]]; then',
            '        COMPREPLY=()',
            sprintf('        for candidate in %s; do', $this->wordList($this->commandNames($definition))),
            '            [[ "$candidate" == "$current"* ]] || continue',
            '',
            '            if [[ "$current" == *:* ]]; then',
            '                COMPREPLY+=( "${candidate##*:}" )',
            '            else',
            '                COMPREPLY+=( "$candidate" )',
            '            fi',
            '        done',
            '        return 0',
            '    fi',
            '',
            '    case "$command" in',
        ];

        foreach ($definition->commands() as $command) {
            $lines[] = sprintf('        %s)', $command->name());
            $this->appendArgumentComment($lines, $command);
            $lines[] = sprintf('            COMPREPLY=( $(compgen -W %s -- "$current") )', $this->words($this->optionNames($command)));
            $lines[] = '            return 0';
            $lines[] = '            ;;';
        }

        $lines[] = '    esac';
        $lines[] = '}';
        $lines[] = sprintf('complete -F _%s_completion %s', $definition->program(), $definition->program());
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

        $lines[] = '            # Arguments: ' . implode(' ', array_map(
            static fn(CompletionArgument $argument): string => $argument->name(),
            $command->arguments(),
        ));
    }

    /**
     * @return list<string>
     */
    private function commandNames(CompletionDefinition $definition): array
    {
        return array_map(
            static fn(CompletionCommand $command): string => $command->name(),
            $definition->commands(),
        );
    }

    /**
     * @return list<string>
     */
    private function optionNames(CompletionCommand $command): array
    {
        $names = [];

        foreach ($command->options() as $option) {
            $names[] = $option->longName();
            $shortName = $option->shortName();

            if ($shortName !== null) {
                $names[] = $shortName;
            }
        }

        return $names;
    }

    /**
     * @param list<string> $values
     */
    private function words(array $values): string
    {
        return $this->escaper->quote(implode(' ', $values));
    }

    /**
     * @param list<string> $values
     */
    private function wordList(array $values): string
    {
        return implode(' ', array_map($this->escaper->quote(...), $values));
    }
}
