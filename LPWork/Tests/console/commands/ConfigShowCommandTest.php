<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Console\Commands\ConfigShowCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\ConfigTestFiles;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    Config::reset();
    ConfigTestFiles::resetDirectory();
});

afterEach(function (): void {
    Config::reset();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('displays all loaded configuration values', function (): void {
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'LPWork'];\n");
    $streams = OutputStreams::create();

    try {
        Config::init(ConfigTestFiles::directory());

        $exitCode = new ConfigShowCommand()->handle(
            new Input(['lpwork', 'config:show']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('Configuration:')
            ->and($streams->stdout())->toContain('| app.name | LPWork |')
            ->and($streams->stderr())->toBe('');
    } finally {
        unlink($file);
    }
});

it('displays one configuration key', function (): void {
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'LPWork', 'debug' => false];\n");
    $streams = OutputStreams::create();

    try {
        Config::init(ConfigTestFiles::directory());

        $exitCode = new ConfigShowCommand()->handle(
            new Input(['lpwork', 'config:show', 'app.name']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('| app.name | LPWork |')
            ->and($streams->stdout())->not->toContain('app.debug')
            ->and($streams->stderr())->toBe('');
    } finally {
        unlink($file);
    }
});

it('hides secrets by default and reveals them when requested', function (): void {
    $file = ConfigTestFiles::createFile('security.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app_key' => 'base64:secret'];\n");
    $redacted = OutputStreams::create();
    $revealed = OutputStreams::create();

    try {
        Config::init(ConfigTestFiles::directory());

        new ConfigShowCommand()->handle(
            new Input(['lpwork', 'config:show', 'security.app_key']),
            new Output($redacted->stdout, $redacted->stderr, decorated: false),
        );

        new ConfigShowCommand()->handle(
            new Input(['lpwork', 'config:show', 'security.app_key', '--show-secrets']),
            new Output($revealed->stdout, $revealed->stderr, decorated: false),
        );

        expect($redacted->stdout())->toContain('| security.app_key | [redacted] |')
            ->and($redacted->stdout())->not->toContain('base64:secret')
            ->and($revealed->stdout())->toContain('| security.app_key | base64:secret |');
    } finally {
        unlink($file);
    }
});
