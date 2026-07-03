<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Renderers\CliExceptionRenderer;

it('renders CLI exceptions with details while debugging', function (): void {
    $rendered = new CliExceptionRenderer(debug: true)->render(new RuntimeException('Debug failure'));

    expect($rendered)->toContain(RuntimeException::class)
        ->and($rendered)->toContain('Debug failure')
        ->and($rendered)->toContain('Stack trace');
});

it('renders CLI production exceptions without secrets environment values or stack traces', function (): void {
    $rendered = new CliExceptionRenderer(debug: false)->render(new RuntimeException('APP_KEY=secret failed'));

    expect($rendered)->toBe("Internal Server Error\n")
        ->and($rendered)->not->toContain('APP_KEY')
        ->and($rendered)->not->toContain('secret')
        ->and($rendered)->not->toContain('Stack trace');
});
