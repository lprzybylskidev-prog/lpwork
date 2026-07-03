<?php

declare(strict_types=1);

use LPWork\Console\FileCreators\FileCreatorDefinitions;

it('keeps generated file templates free of placeholder comment bodies', function (): void {
    foreach (new FileCreatorDefinitions()->all() as $definition) {
        expect((bool) preg_match('/^\s*\/\/\s*$/m', $definition->template()))
            ->toBeFalse("File creator [{$definition->type()}] contains a placeholder comment body.");
    }
});

it('keeps debug renderers split behind focused collaborators', function (): void {
    expect(class_exists(\LPWork\DebugBar\DebugBarValueRenderer::class))->toBeTrue()
        ->and(class_exists(\LPWork\ErrorHandling\Renderers\DebugExceptionDocumentRenderer::class))->toBeTrue()
        ->and(class_exists(\LPWork\Frontend\FrameworkBrandRenderer::class))->toBeTrue()
        ->and(class_exists(\LPWork\Frontend\FrameworkAssetUrls::class))->toBeTrue();
});

it('keeps framework owned dynamic SQL tables behind identifier validation', function (): void {
    $violations = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(\Tests\support\ProjectPaths::root() . '/LPWork', FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        $relativePath = str_replace(\Tests\support\ProjectPaths::root() . '/', '', $path);

        if (!str_starts_with($relativePath, 'LPWork/Cache/')
            && !str_starts_with($relativePath, 'LPWork/Locks/')
            && !str_starts_with($relativePath, 'LPWork/Queue/')
            && !str_starts_with($relativePath, 'LPWork/Schedule/')
            && !str_starts_with($relativePath, 'LPWork/Session/')
        ) {
            continue;
        }

        $contents = file_get_contents($path);

        if (!is_string($contents) || !str_contains($contents, '$this->table')) {
            continue;
        }

        if (!str_contains($contents, 'statement(') && !str_contains($contents, 'select(') && !str_contains($contents, 'query(')) {
            continue;
        }

        if (!str_contains($contents, 'SqlIdentifier::table')) {
            $violations[] = $relativePath;
        }
    }

    expect($violations)->toBe([]);
});
