<?php

declare(strict_types=1);

it('provides the root frontend tooling baseline', function (): void {
    $root = \Tests\support\ProjectPaths::root();
    $package = json_decode((string) file_get_contents($root . '/package.json'), associative: true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($package)) {
        throw new RuntimeException('The root package.json must decode to an object.');
    }

    $scripts = $package['scripts'] ?? null;

    if (! is_array($scripts)) {
        throw new RuntimeException('The root package.json scripts must decode to an object.');
    }

    expect($package['type'])->toBe('module')
        ->and($package['private'])->toBeTrue()
        ->and($scripts)->toHaveKeys([
            'frontend:dev',
            'frontend:build',
            'frontend:typecheck',
            'frontend:lint',
            'frontend:stylelint',
            'frontend:format',
            'frontend:check',
            'frontend:test',
            'browser:test',
            'browser:test:ui',
            'browser:install',
        ])
        ->and($package['devDependencies'])->toHaveKeys([
            '@eslint/js',
            '@playwright/test',
            'eslint',
            'prettier',
            'stylelint',
            'stylelint-config-standard',
            'typescript',
            'typescript-eslint',
            'vite',
            'vitest',
        ]);

    expect(file_exists($root . '/vite.config.ts'))->toBeTrue()
        ->and(file_exists($root . '/tsconfig.json'))->toBeTrue()
        ->and(file_exists($root . '/eslint.config.js'))->toBeTrue()
        ->and(file_exists($root . '/prettier.config.js'))->toBeTrue()
        ->and(file_exists($root . '/stylelint.config.js'))->toBeTrue()
        ->and(file_exists($root . '/vitest.config.ts'))->toBeTrue()
        ->and(file_exists($root . '/playwright.config.mjs'))->toBeTrue();

    expect((string) file_get_contents($root . '/playwright.config.mjs'))
        ->toContain("testDir: './LPWork/Tests/browser'");

    $vitestConfig = (string) file_get_contents($root . '/vitest.config.ts');
    $tsconfig = (string) file_get_contents($root . '/tsconfig.json');

    expect($vitestConfig)
        ->toContain("'App/Modules/*/tests/frontend/**/*.test.ts'")
        ->toContain("'LPWork/Frontend/Resources/**/*.test.ts'");

    expect($vitestConfig)->not->toContain("'tests/frontend/**/*.test.ts'");

    expect($tsconfig)->toContain('"LPWork/Frontend/Resources/**/*.ts"');
    expect($tsconfig)->not->toContain('"tests/**/*.ts"');

    expect($scripts['frontend:stylelint'])->not->toContain('resources/**/*.css');
});
