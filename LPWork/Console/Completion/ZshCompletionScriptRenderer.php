<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use function array_map;
use function implode;

use LPWork\Console\Completion\Contracts\CompletionScriptRenderer;

use function sprintf;

/**
 * Renders zsh completion script renderer output.
 */
final readonly class ZshCompletionScriptRenderer implements CompletionScriptRenderer
{
    /**
     * Creates a new ZshCompletionScriptRenderer instance.
     */
    public function __construct(
        private ShellCompletionEscaper $escaper = new ShellCompletionEscaper(),
    ) {}

    /**
     * Performs the shell operation.
     */
    public function shell(): string
    {
        return 'zsh';
    }

    /**
     * Renders this component into its output representation.
     */
    public function render(CompletionDefinition $definition): string
    {
        $lines = [
            '#compdef ' . $definition->program(),
            '# LPWork shell completion for zsh.',
            sprintf('_%s() {', $definition->program()),
            '    local command current candidate',
            '    command="${words[2]}"',
            '    current="${words[CURRENT]}"',
            '',
            '    if (( CURRENT == 2 )); then',
            '        local -a commands',
            '        local description',
            '        commands=()',
            sprintf('        for candidate in %s; do', $this->wordList($this->commandNames($definition))),
            '            description=""',
            '            case "$candidate" in',
        ];

        foreach ($definition->commands() as $command) {
            $lines[] = sprintf('                %s) description=%s ;;', $command->name(), $this->escaper->quote($command->description()));
        }

        $lines = [
            ...$lines,
            '            esac',
            '',
            '            if [[ "${IPREFIX}${PREFIX}" == *:* ]]; then',
            '                [[ "$candidate" == "${IPREFIX}${PREFIX}"* ]] || continue',
            '                commands+=( "${candidate##*:}:$description" )',
            '            else',
            '                [[ "$candidate" == "$current"* ]] || continue',
            '                commands+=( "${candidate//:/\\:}:$description" )',
            '            fi',
            '        done',
            '',
            '        compset -P "*:"',
            '        _describe "commands" commands',
            '        return',
            '    fi',
            '',
            '    case "$command" in',
        ];

        foreach ($definition->commands() as $command) {
            $lines[] = sprintf('        %s)', $command->name());
            $this->appendArgumentComment($lines, $command);
            $lines[] = '            local -a options';
            $lines[] = '            options=(';

            foreach ($command->options() as $option) {
                $lines[] = sprintf('                %s', $this->escaper->quote($option->longName() . '[' . $option->description() . ']'));

                if ($option->shortName() !== null) {
                    $lines[] = sprintf('                %s', $this->escaper->quote($option->shortName() . '[' . $option->description() . ']'));
                }
            }

            $lines[] = '            )';
            $lines[] = '            _describe "options" options';
            $lines[] = '            return';
            $lines[] = '            ;;';
        }

        $lines[] = '    esac';
        $lines[] = '}';
        $lines[] = sprintf('compdef _%s %s', $definition->program(), $definition->program());
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
     * @param list<string> $values
     */
    private function wordList(array $values): string
    {
        return implode(' ', array_map($this->escaper->quote(...), $values));
    }
}
