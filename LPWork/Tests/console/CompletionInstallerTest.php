<?php

declare(strict_types=1);

use LPWork\Console\Completion\CompletionInstaller;
use LPWork\Console\Exceptions\UnsupportedCompletionShellException;
use Tests\support\ConfigTestFiles;

beforeEach(function (): void {
    ConfigTestFiles::resetDirectory();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('installs bash completion as a managed profile block', function (): void {
    $home = ConfigTestFiles::directory();
    $installer = new CompletionInstaller();

    $installation = $installer->install('bash', $home);
    $contents = file_get_contents($home . '/.bashrc');

    expect($installation->shell())->toBe('bash')
        ->and($installation->file())->toBe($home . '/.bashrc')
        ->and($installation->activationCommand())->toContain('completion:generate bash')
        ->and($contents)->toContain('# >>> lpwork completion >>>')
        ->and($contents)->toContain('source <(lpwork completion:generate bash')
        ->and($contents)->toContain('complete -r lpwork')
        ->and($contents)->toContain('# <<< lpwork completion <<<');
});

it('replaces an existing managed completion block instead of duplicating it', function (): void {
    $home = ConfigTestFiles::directory();
    $installer = new CompletionInstaller();

    $installer->install('bash', $home);
    $installer->install('bash', $home);

    $contents = file_get_contents($home . '/.bashrc');

    expect(substr_count((string) $contents, '# >>> lpwork completion >>>'))->toBe(1)
        ->and(substr_count((string) $contents, '# <<< lpwork completion <<<'))->toBe(1);
});

it('installs zsh and fish completion into their shell profiles', function (string $shell, string $file, string $expected): void {
    $home = ConfigTestFiles::directory();
    $installer = new CompletionInstaller();

    $installation = $installer->install($shell, $home);
    $contents = file_get_contents($home . '/' . $file);

    expect($installation->shell())->toBe($shell)
        ->and($installation->file())->toBe($home . '/' . $file)
        ->and($contents)->toContain($expected);
})->with([
    ['zsh', '.zshrc', 'completion:generate zsh'],
    ['fish', '.config/fish/conf.d/lpwork.fish', 'completion:generate fish'],
]);

it('rejects unsupported shells explicitly', function (): void {
    expect(fn(): mixed => new CompletionInstaller()->install('powershell', ConfigTestFiles::directory()))
        ->toThrow(UnsupportedCompletionShellException::class, 'Unsupported shell [powershell]. Supported shells: bash, zsh, fish.');
});
