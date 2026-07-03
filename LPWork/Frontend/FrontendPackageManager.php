<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Frontend\Exceptions\InvalidFrontendCommandException;

use function trim;

/**
 * Enumerates the supported frontend package manager values.
 */
enum FrontendPackageManager: string
{
    case Pnpm = 'pnpm';
    case Yarn = 'yarn';
    case Bun = 'bun';
    case Npm = 'npm';

    /**
     * @return non-empty-list<string>
     */
    public function installCommand(): array
    {
        return match ($this) {
            self::Pnpm => ['pnpm', 'install'],
            self::Yarn => ['yarn', 'install'],
            self::Bun => ['bun', 'install'],
            self::Npm => ['npm', 'install'],
        };
    }

    /**
     * @return non-empty-list<string>
     */
    public function runScriptCommand(string $script): array
    {
        if (trim($script) === '') {
            throw InvalidFrontendCommandException::emptyScriptName();
        }

        return match ($this) {
            self::Pnpm => ['pnpm', 'run', $script],
            self::Yarn => ['yarn', 'run', $script],
            self::Bun => ['bun', 'run', $script],
            self::Npm => ['npm', 'run', $script],
        };
    }
}
