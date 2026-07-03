<?php

declare(strict_types=1);

namespace Tests\support\health;

use Tests\support\testing\ApplicationTestHarness;

final readonly class HealthTestHarness
{
    public static function healthy(): ApplicationTestHarness
    {
        $harness = ApplicationTestHarness::fromProjectDefaults()
            ->setEnvValue('DB_SQLITE_DATABASE', ':memory:');

        foreach (self::frontendFiles() as $file) {
            $harness->copyFromProjectIfExists($file);
        }

        return $harness;
    }

    /**
     * @return list<string>
     */
    private static function frontendFiles(): array
    {
        return [
            'package.json',
            'package-lock.json',
            'tsconfig.json',
            'eslint.config.js',
            'prettier.config.js',
            'stylelint.config.js',
            'vite.config.ts',
            'vitest.config.ts',
            'playwright.config.mjs',
        ];
    }

}
