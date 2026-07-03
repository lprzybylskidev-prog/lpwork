<?php

declare(strict_types=1);

use LPWork\Console\CommandRegistry;
use LPWork\Console\Completion\CompletionScriptGenerator;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\HiddenCommand;
use LPWork\Console\Exceptions\UnsupportedCompletionShellException;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\console\DescribedCommand;
use Tests\support\console\TestCommand;

it('generates bash completion from registered commands and described options', function (): void {
    $registry = new CommandRegistry();
    $registry->add(new TestCommand('cache:clear', 'Clear cache.'));
    $registry->add(new DescribedCommand());

    $script = CompletionScriptGenerator::default()->generate('bash', $registry);

    expect($script)->toContain('# LPWork shell completion for bash.')
        ->and($script)->toContain('complete -F _lpwork_completion lpwork')
        ->and($script)->toContain('current="${line##* }"')
        ->and($script)->toContain('command="${words%% *}"')
        ->and($script)->toContain("for candidate in 'cache:clear' 'users:import'; do")
        ->and($script)->toContain('COMPREPLY+=( "${candidate##*:}" )')
        ->and($script)->toContain('# Arguments: file mode')
        ->and($script)->toContain("compgen -W '--help -h --force -f --path -p --tag'");
});

it('generates zsh completion with command and option descriptions', function (): void {
    $registry = new CommandRegistry();
    $registry->add(new DescribedCommand());

    $script = CompletionScriptGenerator::default()->generate('zsh', $registry);

    expect($script)->toContain('#compdef lpwork')
        ->and($script)->toContain('if [[ "${IPREFIX}${PREFIX}" == *:* ]]; then')
        ->and($script)->toContain('[[ "$candidate" == "${IPREFIX}${PREFIX}"* ]] || continue')
        ->and($script)->toContain('commands+=( "${candidate##*:}:$description" )')
        ->and($script)->toContain('commands+=( "${candidate//:/\\:}:$description" )')
        ->and($script)->toContain('compset -P "*:"')
        ->and($script)->toContain('users:import) description=\'Import users.\' ;;')
        ->and($script)->toContain('# Arguments: file mode')
        ->and($script)->toContain("'--force[Force import.]'")
        ->and($script)->toContain("'-f[Force import.]'");
});

it('generates fish completion with command and option descriptions', function (): void {
    $registry = new CommandRegistry();
    $registry->add(new DescribedCommand());

    $script = CompletionScriptGenerator::default()->generate('fish', $registry);

    expect($script)->toContain('# LPWork shell completion for fish.')
        ->and($script)->toContain('# users:import arguments: file mode')
        ->and($script)->toContain("complete -c lpwork -f -n '__fish_use_subcommand' -a 'users:import' -d 'Import users.'")
        ->and($script)->toContain("complete -c lpwork -f -n '__fish_seen_subcommand_from users:import' -l force -d 'Force import.' -s f");
});

it('rejects unsupported shells', function (): void {
    $registry = new CommandRegistry();

    expect(fn() => CompletionScriptGenerator::default()->generate('powershell', $registry))
        ->toThrow(UnsupportedCompletionShellException::class, 'Unsupported shell [powershell]. Supported shells: bash, zsh, fish.');
});

it('does not suggest hidden internal commands', function (): void {
    $registry = new CommandRegistry();
    $registry->add(new TestCommand('completion:install', 'Install completion.'));
    $registry->add(new class implements Command, HiddenCommand {
        public function name(): string
        {
            return 'completion:generate';
        }

        public function description(): string
        {
            return 'Generate completion.';
        }

        public function handle(Input $input, Output $output): int
        {
            return 0;
        }
    });

    $script = CompletionScriptGenerator::default()->generate('bash', $registry);

    expect($script)->toContain("'completion:install'")
        ->and($script)->not->toContain('completion:generate');
});
