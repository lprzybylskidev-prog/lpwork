<?php

declare(strict_types=1);

use LPWork\Config\ConfigShowRenderer;
use LPWork\Console\Output;
use Tests\support\console\OutputStreams;

it('renders loaded configuration as flattened rows', function (): void {
    $streams = OutputStreams::create();

    new ConfigShowRenderer()->render([
        'app' => [
            'name' => 'LPWork',
            'debug' => true,
            'empty' => [],
        ],
        'routing' => [
            'middleware' => [
                'global' => ['web'],
            ],
        ],
    ], new Output($streams->stdout, $streams->stderr, decorated: false));

    expect($streams->stdout())->toContain('Configuration:')
        ->and($streams->stdout())->toContain('| Key                         | Value  |')
        ->and($streams->stdout())->toContain('| app.name                    | LPWork |')
        ->and($streams->stdout())->toContain('| app.debug                   | true   |')
        ->and($streams->stdout())->toContain('| app.empty                   | []     |')
        ->and($streams->stdout())->toContain('| routing.middleware.global.0 | web    |')
        ->and($streams->stderr())->toBe('');
});

it('redacts sensitive values unless secrets are requested', function (): void {
    $redacted = OutputStreams::create();
    $revealed = OutputStreams::create();
    $config = [
        'security' => [
            'app_key' => 'base64:secret',
            'csrf_token_name' => '_token',
        ],
    ];

    new ConfigShowRenderer()->render($config, new Output($redacted->stdout, $redacted->stderr, decorated: false));
    new ConfigShowRenderer()->render($config, new Output($revealed->stdout, $revealed->stderr, decorated: false), showSecrets: true);

    expect($redacted->stdout())->toContain('| security.app_key         | [redacted] |')
        ->and($redacted->stdout())->toContain('| security.csrf_token_name | [redacted] |')
        ->and($redacted->stdout())->not->toContain('base64:secret')
        ->and($revealed->stdout())->toContain('| security.app_key         | base64:secret |')
        ->and($revealed->stdout())->toContain('| security.csrf_token_name | _token        |');
});

it('renders an empty configuration message', function (): void {
    $streams = OutputStreams::create();

    new ConfigShowRenderer()->render([], new Output($streams->stdout, $streams->stderr, decorated: false));

    expect($streams->stdout())->toBe("No configuration values loaded.\n")
        ->and($streams->stderr())->toBe('');
});
