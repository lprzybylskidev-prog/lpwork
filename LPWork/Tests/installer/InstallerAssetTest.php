<?php

declare(strict_types=1);

use Tests\support\ProjectPaths;

it('ships standalone installers that refuse to run until a tagged release archive URL is configured', function (): void {
    $unixInstaller = ProjectPaths::root('installers/install-lpwork.sh');
    $powershellInstaller = ProjectPaths::root('installers/Install-LPWork.ps1');

    expect($unixInstaller)->toBeFile()
        ->and($powershellInstaller)->toBeFile();

    $unix = file_get_contents($unixInstaller);
    $powershell = file_get_contents($powershellInstaller);

    expect($unix)->toContain('__LPWORK_RELEASE_ARCHIVE_URL__')
        ->and($powershell)->toContain('__LPWORK_RELEASE_ARCHIVE_URL__')
        ->and($unix)->toContain('/archive/refs/tags/')
        ->and($powershell)->toContain('/archive/refs/tags/')
        ->and($unix)->toContain('releases/download/')
        ->and($powershell)->toContain('releases/download/')
        ->and($unix)->not->toContain('git clone')
        ->and($powershell)->not->toContain('git clone')
        ->and($unix)->not->toContain('composer install')
        ->and($powershell)->not->toContain('composer install')
        ->and($unix)->not->toContain('npm install')
        ->and($powershell)->not->toContain('npm install');

    $output = [];
    $exitCode = 0;
    exec('sh ' . escapeshellarg($unixInstaller) . ' Blog 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(1)
        ->and(implode("\n", $output))->toContain('no immutable LPWork release archive URL is configured');
});

it('defines generated application cleanup and guidance asset locations', function (): void {
    $unix = file_get_contents(ProjectPaths::root('installers/install-lpwork.sh'));
    $powershell = file_get_contents(ProjectPaths::root('installers/Install-LPWork.ps1'));

    foreach ([
        '.git',
        '.githooks',
        'hooks',
        'lpwork-roadmap',
        'AGENTS.md',
        'vendor',
        'node_modules',
        'installers',
        'storage/cache',
        'storage/log',
        'storage/playwright',
        'storage/test-reports',
        'storage/tmp',
        'storage/testing',
        'storage/database.sqlite',
    ] as $removedPath) {
        expect($unix)->toContain($removedPath)
            ->and($powershell)->toContain($removedPath);
    }

    expect($unix)->toContain('docs/.AGENTS.md')
        ->and($powershell)->toContain('docs/.AGENTS.md')
        ->and($unix)->toContain('AGENTS.md')
        ->and($powershell)->toContain('AGENTS.md')
        ->and($unix)->not->toContain('remove_path ".devcontainer"')
        ->and($powershell)->not->toContain("'.devcontainer'")
        ->and($unix)->not->toContain('remove_path "LPWork/Tests"')
        ->and($powershell)->not->toContain("'LPWork/Tests'");

    $applicationAgents = file_get_contents(ProjectPaths::root('docs/.AGENTS.md'));
    $applicationSkill = file_get_contents(ProjectPaths::root('.codex/skills/lpwork-application/SKILL.md'));
    $moduleSkill = file_get_contents(ProjectPaths::root('.codex/skills/lpwork-module-development/SKILL.md'));

    expect(ProjectPaths::root('docs/.AGENTS.md'))->toBeFile()
        ->and($applicationAgents)->toContain('These instructions apply to LPWork applications')
        ->and($applicationAgents)->toContain('Required Agent Onboarding')
        ->and($applicationAgents)->toContain('.codex/skills/lpwork-application/SKILL.md')
        ->and($applicationAgents)->toContain('php lpwork check')
        ->and($applicationAgents)->toContain('php lpwork format')
        ->and(ProjectPaths::root('.codex/skills/lpwork-application/SKILL.md'))->toBeFile()
        ->and(ProjectPaths::root('.codex/skills/lpwork-application/references/navigation.md'))->toBeFile()
        ->and(ProjectPaths::root('.codex/skills/lpwork-application/references/architecture.md'))->toBeFile()
        ->and(ProjectPaths::root('.codex/skills/lpwork-application/references/quality.md'))->toBeFile()
        ->and(ProjectPaths::root('.codex/skills/lpwork-application/references/runtime-services.md'))->toBeFile()
        ->and($applicationSkill)->toContain('name: lpwork-application')
        ->and($applicationSkill)->toContain('# LPWork Application')
        ->and($applicationSkill)->toContain('Treat `LPWork` as framework code')
        ->and($applicationSkill)->toContain('references/quality.md')
        ->and(ProjectPaths::root('.codex/skills/lpwork-module-development/SKILL.md'))->toBeFile()
        ->and(ProjectPaths::root('.codex/skills/lpwork-module-development/references/module-structure.md'))->toBeFile()
        ->and(ProjectPaths::root('.codex/skills/lpwork-module-development/references/module-quality.md'))->toBeFile()
        ->and($moduleSkill)->toContain('name: lpwork-module-development')
        ->and($moduleSkill)->toContain('# LPWork Module Development')
        ->and($moduleSkill)->toContain('Register HTTP routes through the module route provider')
        ->and($moduleSkill)->toContain('references/module-quality.md');
});

it('ships source available repository release assets and framework ci', function (): void {
    $readme = file_get_contents(ProjectPaths::root('README.md'));
    $license = file_get_contents(ProjectPaths::root('LICENSE.md'));
    $workflow = file_get_contents(ProjectPaths::root('.github/workflows/ci.yml'));
    $gitignore = file_get_contents(ProjectPaths::root('.gitignore'));
    $hook = file_get_contents(ProjectPaths::root('.githooks/pre-commit'));

    expect($readme)->toContain('source-available portfolio snapshot')
        ->and($readme)->toContain('v1.0.0')
        ->and($readme)->toContain('php lpwork about')
        ->and($readme)->toContain('php lpwork test:lpwork')
        ->and($readme)->toContain('php lpwork check')
        ->and($readme)->toContain('The default welcome page and `php lpwork about` use the same framework module catalog')
        ->and($license)->toContain('portfolio, CV, review, and recruitment purposes only')
        ->and($license)->toContain('No other rights are granted')
        ->and($license)->toContain('use the software or any substantial part of it')
        ->and($license)->not->toContain('MIT License')
        ->and($license)->not->toContain('Apache License')
        ->and($license)->not->toContain('GNU GENERAL PUBLIC LICENSE')
        ->and($workflow)->toContain('composer install')
        ->and($workflow)->toContain('npm ci')
        ->and($workflow)->toContain('php lpwork test:lpwork')
        ->and($workflow)->toContain('php lpwork check')
        ->and($workflow)->not->toContain('php lpwork test' . "\n")
        ->and($gitignore)->toContain('/.env')
        ->and($gitignore)->toContain('!/.env.example')
        ->and($gitignore)->toContain('/lpwork-roadmap')
        ->and($gitignore)->toContain('/storage/*')
        ->and($hook)->toContain('php lpwork format --backend')
        ->and($hook)->toContain('php lpwork test:lpwork')
        ->and($hook)->toContain('php lpwork check');
});
