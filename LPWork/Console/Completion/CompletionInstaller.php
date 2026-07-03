<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use LPWork\Console\Exceptions\UnsupportedCompletionShellException;
use LPWork\Filesystem\Filesystem;

use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function trim;

/**
 * Represents the completion installer framework component.
 */
final readonly class CompletionInstaller
{
    private const BEGIN_MARKER = '# >>> lpwork completion >>>';

    private const END_MARKER = '# <<< lpwork completion <<<';

    /**
     * Creates a new CompletionInstaller instance.
     */
    public function __construct(
        private Filesystem $files = new Filesystem(),
    ) {}

    /**
     * Performs the install operation.
     */
    public function install(string $shell, string $homePath): CompletionInstallation
    {
        $shell = $this->normalizeShell($shell);
        $file = $this->profilePath($shell, $homePath);
        $block = $this->block($shell);
        $contents = $this->files->exists($file) ? $this->files->read($file) : '';

        $this->files->write($file, $this->withManagedBlock($contents, $block));

        return new CompletionInstallation($shell, $file, $this->activationCommand($shell));
    }

    /**
     * @return list<string>
     */
    public function supportedShells(): array
    {
        return ['bash', 'zsh', 'fish'];
    }

    /**
     * Builds or returns normalize shell.
     */
    public function normalizeShell(string $shell): string
    {
        $shell = trim($shell);

        if (str_ends_with($shell, '/bash')) {
            return 'bash';
        }

        if (str_ends_with($shell, '/zsh')) {
            return 'zsh';
        }

        if (str_ends_with($shell, '/fish')) {
            return 'fish';
        }

        if ($shell === 'bash' || $shell === 'zsh' || $shell === 'fish') {
            return $shell;
        }

        throw UnsupportedCompletionShellException::forShell($shell, $this->supportedShells());
    }

    private function profilePath(string $shell, string $homePath): string
    {
        $homePath = rtrim($homePath, '/');

        return match ($shell) {
            'bash' => $homePath . '/.bashrc',
            'zsh' => $homePath . '/.zshrc',
            'fish' => $homePath . '/.config/fish/conf.d/lpwork.fish',
            default => throw UnsupportedCompletionShellException::forShell($shell, $this->supportedShells()),
        };
    }

    private function block(string $shell): string
    {
        return match ($shell) {
            'bash' => <<<'BASH'
                if command -v lpwork >/dev/null 2>&1 && [ -n "${BASH_VERSION:-}" ]; then
                    complete -r lpwork 2>/dev/null || true
                    unset -f _lpwork_completion 2>/dev/null || true
                    source <(lpwork completion:generate bash 2>/dev/null || true)
                fi
                BASH,
            'zsh' => <<<'ZSH'
                if command -v lpwork >/dev/null 2>&1 && [ -n "${ZSH_VERSION:-}" ]; then
                    unfunction _lpwork 2>/dev/null || true
                    compdef -d lpwork 2>/dev/null || true
                    eval "$(lpwork completion:generate zsh 2>/dev/null || true)"
                fi
                ZSH,
            'fish' => <<<'FISH'
                if command -q lpwork
                    lpwork completion:generate fish 2>/dev/null | source
                end
                FISH,
            default => throw UnsupportedCompletionShellException::forShell($shell, $this->supportedShells()),
        };
    }

    private function activationCommand(string $shell): string
    {
        return match ($shell) {
            'bash' => 'complete -r lpwork 2>/dev/null; unset -f _lpwork_completion 2>/dev/null; source <(lpwork completion:generate bash)',
            'zsh' => 'unfunction _lpwork 2>/dev/null; compdef -d lpwork 2>/dev/null; eval "$(lpwork completion:generate zsh)"',
            'fish' => 'lpwork completion:generate fish | source',
            default => throw UnsupportedCompletionShellException::forShell($shell, $this->supportedShells()),
        };
    }

    private function withManagedBlock(string $contents, string $block): string
    {
        $managedBlock = self::BEGIN_MARKER . "\n" . $block . "\n" . self::END_MARKER;
        $contents = trim($this->withoutManagedBlock($contents));

        if ($contents === '') {
            return $managedBlock . "\n";
        }

        return $contents . "\n\n" . $managedBlock . "\n";
    }

    private function withoutManagedBlock(string $contents): string
    {
        if (!str_contains($contents, self::BEGIN_MARKER) || !str_contains($contents, self::END_MARKER)) {
            return $contents;
        }

        $pattern = '/' . preg_quote(self::BEGIN_MARKER, '/') . '.*?' . preg_quote(self::END_MARKER, '/') . "\\R?/s";

        return preg_replace($pattern, '', $contents) ?? $contents;
    }
}
