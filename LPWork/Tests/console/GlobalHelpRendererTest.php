<?php

declare(strict_types=1);

use LPWork\Console\GlobalHelpRenderer;
use LPWork\Console\Output;
use Tests\support\console\OutputStreams;

it('renders a focused global console help guide', function (): void {
    $streams = OutputStreams::create();

    new GlobalHelpRenderer()->render(
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toContain('LPWork Console Help')
        ->and($streams->stdout())->toContain('lpwork <command> [arguments] [options]')
        ->and($streams->stdout())->toContain('Global Options:')
        ->and($streams->stdout())->toContain('-h, --help')
        ->and($streams->stdout())->toContain('--module=VALUE')
        ->and($streams->stdout())->toContain('Input Conventions:')
        ->and($streams->stdout())->toContain('--tag=VALUE...')
        ->and($streams->stdout())->toContain('Common Workflows:')
        ->and($streams->stdout())->toContain('lpwork test --module=Blog')
        ->and($streams->stdout())->toContain('lpwork test:lpwork')
        ->and($streams->stdout())->toContain('lpwork test:lpwork --browser')
        ->and($streams->stdout())->toContain('lpwork frontend:build')
        ->and($streams->stdout())->toContain('lpwork --module=Blog make:controller HomeController')
        ->and($streams->stdout())->toContain('More Help:')
        ->and($streams->stderr())->toBe('');
});
