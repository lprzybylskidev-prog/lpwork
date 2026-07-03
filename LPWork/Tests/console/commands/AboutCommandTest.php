<?php

declare(strict_types=1);

use LPWork\Foundation\FrameworkMetadata;

use function PHPUnit\Framework\assertLessThan;
use function PHPUnit\Framework\assertNotFalse;

use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\CliTestClient;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('summarizes runtime environment cache state and framework modules', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    $result = CliTestClient::forApplication($harness->bootstrap(['lpwork', 'about']))
        ->command('about')
        ->assertSuccessful()
        ->assertStdoutContains('LPWork application information')
        ->assertStdoutContains('Application')
        ->assertStdoutContains('Runtime')
        ->assertStdoutContains('Configured services')
        ->assertStdoutContains('Compiled caches')
        ->assertStdoutContains('Framework')
        ->assertStdoutContains('Framework version')
        ->assertStdoutContains(FrameworkMetadata::VERSION)
        ->assertStdoutContains('PHP version')
        ->assertStdoutContains(PHP_VERSION)
        ->assertStdoutContains('PHP SAPI')
        ->assertStdoutContains('Environment')
        ->assertStdoutContains('development')
        ->assertStdoutContains('Locale')
        ->assertStdoutContains('Debug')
        ->assertStdoutContains('yes')
        ->assertStdoutContains('Session driver')
        ->assertStdoutContains('php')
        ->assertStdoutContains('Storage disk')
        ->assertStdoutContains('local')
        ->assertStdoutContains('Lock driver')
        ->assertStdoutContains('cache')
        ->assertStdoutContains('Throttle storage')
        ->assertStdoutContains('cache')
        ->assertStdoutContains('Broadcasting connection')
        ->assertStdoutContains('log')
        ->assertStdoutContains('Notification storage')
        ->assertStdoutContains('Scheduler history')
        ->assertStdoutContains('enabled')
        ->assertStdoutContains('Observability reporters')
        ->assertStdoutContains('null')
        ->assertStdoutContains('Maintenance store')
        ->assertStdoutContains('storage/framework/maintenance.json')
        ->assertStdoutContains('Security profile')
        ->assertStdoutContains('Security headers')
        ->assertStdoutContains('CSRF protection')
        ->assertStdoutContains('Configuration cache')
        ->assertStdoutContains('not cached')
        ->assertStdoutContains('Framework modules')
        ->assertStdoutContains('34')
        ->assertStdoutContains('Health')
        ->assertStdoutContains('HTTP and CLI health checks for runtime readiness.')
        ->assertStdoutContains('Debugbar')
        ->assertNoStderr();

    $stdout = $result->stdout();

    $compiledCachesPosition = strpos($stdout, 'Compiled caches');
    $frameworkPosition = strpos($stdout, 'Framework');
    $frameworkModulesPosition = strpos($stdout, 'Framework modules');

    assertNotFalse($compiledCachesPosition);
    assertNotFalse($frameworkPosition);
    assertNotFalse($frameworkModulesPosition);
    assertLessThan($frameworkPosition, $compiledCachesPosition);
    assertLessThan($frameworkModulesPosition, $frameworkPosition);
});
