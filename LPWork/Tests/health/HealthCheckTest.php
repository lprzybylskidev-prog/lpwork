<?php

declare(strict_types=1);

use LPWork\Console\CommandRegistry;
use LPWork\Filesystem\Filesystem;
use LPWork\Health\Checks\DevelopmentToolsHealthCheck;
use LPWork\Health\Checks\FrontendHealthConfiguration;
use LPWork\Health\Checks\FrontendQualityHealthCheck;
use LPWork\Health\Checks\PhpRuntimeHealthCheck;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\Contracts\PhpRuntimeInspector;
use LPWork\Health\HealthCheckRegistry;
use LPWork\Health\HealthCheckResult;
use Tests\support\health\HealthTestHarness;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\CliTestClient;
use Tests\support\testing\Http\HttpTestClient;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('registers core runtime dependency checks and the health command', function (): void {
    $app = HealthTestHarness::healthy()->bootstrap(['lpwork', 'list']);
    $checks = $app->container()->make(HealthCheckRegistry::class);
    $commands = $app->container()->make(CommandRegistry::class);

    expect($checks)->toBeInstanceOf(HealthCheckRegistry::class)
        ->and($commands)->toBeInstanceOf(CommandRegistry::class);

    if (!$checks instanceof HealthCheckRegistry || !$commands instanceof CommandRegistry) {
        return;
    }

    expect(array_map(
        static fn(HealthCheck $check): string => $check->name(),
        $checks->all(),
    ))->toBe([
        'php',
        'framework.modules',
        'runtime.directories',
        'compiled_caches',
        'development.tools',
        'development.php_extensions',
        'frontend.runtime',
        'frontend.quality',
        'frontend.testing',
        'frontend.build',
        'storage',
        'cache',
        'locks',
        'observability',
        'logging',
        'mail',
        'broadcasting',
        'database',
        'queue',
        'notifications',
        'scheduler',
        'security',
        'session',
        'throttle',
        'translation',
        'views',
        'console',
        'routing',
    ])
        ->and($commands->has('health:check'))->toBeTrue();
});

it('returns a healthy HTTP response when core runtime checks pass', function (): void {
    $app = HealthTestHarness::healthy()->bootstrap();

    HttpTestClient::forApplication($app)
        ->get('/health')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.0.name', 'php')
        ->assertJsonPath('checks.1.name', 'framework.modules')
        ->assertJsonPath('checks.3.name', 'compiled_caches')
        ->assertJsonPath('checks.4.name', 'development.tools')
        ->assertJsonPath('checks.6.name', 'frontend.runtime')
        ->assertJsonPath('checks.9.name', 'frontend.build')
        ->assertJsonPath('checks.10.name', 'storage')
        ->assertJsonPath('checks.19.name', 'notifications')
        ->assertJsonPath('checks.27.name', 'routing');
});

it('runs core runtime checks through the CLI', function (): void {
    $app = HealthTestHarness::healthy()->bootstrap(['lpwork', 'health:check']);

    $result = CliTestClient::forApplication($app)
        ->command('health:check');

    $result
        ->assertSuccessful()
        ->assertStdoutContains("Health: ok\n")
        ->assertStdoutContains("Summary: 28 ok, 0 failed\n")
        ->assertStdoutContains('| Runtime     | php')
        ->assertStdoutContains('| Framework   | modules')
        ->assertStdoutContains('| Runtime     | directories')
        ->assertStdoutContains('| Framework   | compiled_caches')
        ->assertStdoutContains('| Development | tools')
        ->assertStdoutContains('| Development | php_extensions')
        ->assertStdoutContains('| Frontend    | runtime')
        ->assertStdoutContains('| Frontend    | quality')
        ->assertStdoutContains('| Frontend    | testing')
        ->assertStdoutContains('| Frontend    | build')
        ->assertStdoutContains('| Services    | storage')
        ->assertStdoutContains('| Services    | cache')
        ->assertStdoutContains('| Framework   | locks')
        ->assertStdoutContains('| Framework   | console')
        ->assertStdoutContains('| Framework   | observability')
        ->assertStdoutContains('| Framework   | logging')
        ->assertStdoutContains('| Framework   | mail')
        ->assertStdoutContains('| Framework   | broadcasting')
        ->assertStdoutContains('| Services    | database')
        ->assertStdoutContains('| Services    | queue')
        ->assertStdoutContains('| Framework   | notifications')
        ->assertStdoutContains('| Framework   | scheduler')
        ->assertStdoutContains('| Framework   | security')
        ->assertStdoutContains('| Framework   | session')
        ->assertStdoutContains('| Framework   | throttle')
        ->assertStdoutContains('| Framework   | translation')
        ->assertStdoutContains('| Framework   | views')
        ->assertStdoutContains('| Framework   | routing')
        ->assertStdoutContains('Storage disk [local] using driver [local] is readable and writable.')
        ->assertStdoutContains('Cache store [framework] using driver [file] is readable and writable.')
        ->assertStdoutContains('Database connection [sqlite] using driver [sqlite] at [:memory:]')
        ->assertStdoutContains('Queue connection [sync] using driver [sync] is ready at [sync].')
        ->assertNoStderr();

    foreach (array_filter(explode("\n", $result->stdout()), static fn(string $line): bool => $line !== '') as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(120);
    }
});

it('reports corrupted compiled cache files', function (): void {
    $harness = HealthTestHarness::healthy();
    $app = $harness->bootstrap(['lpwork', 'health:check']);
    $harness->writeFile('storage/framework/cache/config.php', "<?php\n\nreturn 'bad';\n");

    HttpTestClient::forApplication($app)
        ->get('/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'failed')
        ->assertJsonFragment([
            'name' => 'compiled_caches',
            'status' => 'failed',
            'message' => 'Configuration cache did not return an array.',
        ]);
});

it('reports missing PHP runtime requirements', function (): void {
    HealthTestHarness::healthy()->bootstrap();

    $check = new PhpRuntimeHealthCheck(new class implements PhpRuntimeInspector {
        public function phpVersionId(): int
        {
            return 80499;
        }

        public function phpVersion(): string
        {
            return '8.4.99';
        }

        public function extensionLoaded(string $extension): bool
        {
            return $extension === 'json';
        }

        public function pdoDrivers(): array
        {
            return [];
        }
    });

    $result = $check->check();

    expect($result->isHealthy())->toBeFalse()
        ->and($result->message())->toContain('PHP 8.5 or newer is required; current version is 8.4.99.')
        ->and($result->message())->toContain('Missing PHP extension [pdo].')
        ->and($result->message())->toContain('Missing PHP extension [session].')
        ->and($result->message())->toContain('Missing PDO driver [sqlite] for the configured default database connection.');
});

it('keeps development tooling checks limited to commands used by built-in workflows', function (): void {
    $result = new DevelopmentToolsHealthCheck(
        path: '/missing',
        requiredCommands: ['composer', 'node', 'npm'],
    )->check();

    expect($result->isHealthy())->toBeFalse()
        ->and($result->name())->toBe('development.tools')
        ->and($result->message())->toBe('Missing development commands: composer, node, npm.');
});

it('reports missing frontend code quality diagnostics', function (): void {
    $harness = ApplicationTestHarness::create()
        ->writeFile('package.json', json_encode([
            'scripts' => [
                'frontend:typecheck' => 'tsc --noEmit',
            ],
            'devDependencies' => [
                'typescript' => '^6.0.0',
            ],
        ], JSON_THROW_ON_ERROR));

    $result = new FrontendQualityHealthCheck(new FrontendHealthConfiguration($harness->basePath(), new Filesystem()))->check();

    expect($result->isHealthy())->toBeFalse()
        ->and($result->message())->toContain('Missing frontend quality config files')
        ->and($result->message())->toContain('frontend:lint')
        ->and($result->message())->toContain('eslint');
});

it('reports unhealthy checks through HTTP and CLI entrypoints', function (): void {
    $app = HealthTestHarness::healthy()->bootstrap(['lpwork', 'health:check']);
    $registry = $app->container()->make(HealthCheckRegistry::class);

    expect($registry)->toBeInstanceOf(HealthCheckRegistry::class);

    if (!$registry instanceof HealthCheckRegistry) {
        return;
    }

    $registry->add(new class implements HealthCheck {
        public function name(): string
        {
            return 'failing';
        }

        public function check(): HealthCheckResult
        {
            throw new \RuntimeException('sensitive failure detail');
        }
    });

    HttpTestClient::forApplication($app)
        ->get('/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'failed')
        ->assertJsonFragment([
            'name' => 'failing',
            'status' => 'failed',
            'message' => 'Check failed with RuntimeException.',
        ])
        ->assertDontSee('sensitive failure detail');

    CliTestClient::forApplication($app)
        ->command('health:check')
        ->assertFailed()
        ->assertStdoutContains("Health: failed\n")
        ->assertStdoutContains('| Framework   | failing')
        ->assertStdoutContains('| failed')
        ->assertStdoutContains('Check failed with RuntimeException.')
        ->assertNoStderr();
});

it('reports database queue readiness failures when queue storage is missing', function (): void {
    $app = HealthTestHarness::healthy()
        ->setEnvValue('QUEUE_CONNECTION', 'database')
        ->bootstrap(['lpwork', 'health:check']);

    HttpTestClient::forApplication($app)
        ->get('/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'failed')
        ->assertJsonFragment([
            'name' => 'queue',
            'status' => 'failed',
            'message' => 'Queue connection [database] using driver [database] at [connection [default] table [queue_jobs]] failed: LPWork\Database\Exceptions\DatabaseQueryException.',
        ]);

    CliTestClient::forApplication($app)
        ->command('health:check')
        ->assertFailed()
        ->assertStdoutContains("Health: failed\n")
        ->assertStdoutContains('| Services    | queue')
        ->assertStdoutContains('Queue connection [database] using driver [database]')
        ->assertNoStderr();
});

it('reports broken configured database values instead of assuming devcontainer services', function (): void {
    $app = HealthTestHarness::healthy()
        ->setEnvValue('DB_CONNECTION', 'mysql')
        ->setEnvValue('DB_MYSQL_HOST', 'missing-mysql-host')
        ->setEnvValue('DB_MYSQL_PORT', '3306')
        ->setEnvValue('DB_MYSQL_DATABASE', 'lpwork_missing')
        ->setEnvValue('DB_MYSQL_USERNAME', 'lpwork')
        ->setEnvValue('DB_MYSQL_PASSWORD', 'secret')
        ->setEnvValue('DB_MYSQL_CHARSET', 'utf8mb4')
        ->bootstrap(['lpwork', 'health:check']);

    HttpTestClient::forApplication($app)
        ->get('/health')
        ->assertStatus(503)
        ->assertJsonFragment([
            'name' => 'database',
            'status' => 'failed',
            'message' => 'Database connection [mysql] using driver [mysql] at [missing-mysql-host:3306/lpwork_missing] failed: LPWork\Database\Exceptions\DatabaseConnectionException.',
        ]);

    CliTestClient::forApplication($app)
        ->command('health:check')
        ->assertFailed()
        ->assertStdoutContains("Health: failed\n")
        ->assertStdoutContains('| Services    | database')
        ->assertStdoutContains('Database connection [mysql] using driver [mysql]')
        ->assertNoStderr();
});
