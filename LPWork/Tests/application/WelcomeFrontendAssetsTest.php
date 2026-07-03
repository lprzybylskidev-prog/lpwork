<?php

declare(strict_types=1);

it('provides the welcome module frontend entrypoint files', function (): void {
    $root = \Tests\support\ProjectPaths::root();
    $entry = $root . '/App/Modules/Welcome/resources/frontend/app.ts';
    $stylesheet = $root . '/App/Modules/Welcome/resources/frontend/app.css';

    expect(file_exists($entry))->toBeTrue()
        ->and(file_exists($stylesheet))->toBeTrue()
        ->and(file_get_contents($entry))->toContain("import './app.css';");
});
