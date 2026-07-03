<?php

declare(strict_types=1);

use LPWork\Config\ConfigCache;
use LPWork\Console\Commands\ConfigClearCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\ConfigTestFiles;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    ConfigTestFiles::resetDirectory();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('clears the compiled config cache and reports success', function (): void {
    $basePath = ConfigTestFiles::directory();
    $cache = new ConfigCache($basePath, 'cache/config.php');
    $streams = OutputStreams::create();
    $command = new ConfigClearCommand($cache);

    ConfigTestFiles::createFile(
        'cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['name' => 'LPWork']];\n",
        $basePath,
    );

    $exitCode = $command->handle(
        new Input(['lpwork', 'config:clear']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('Configuration cache cleared successfully.')
        ->and($streams->stderr())->toBe('')
        ->and($cache->exists())->toBeFalse();
});
