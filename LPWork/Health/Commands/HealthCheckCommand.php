<?php

declare(strict_types=1);

namespace LPWork\Health\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Health\HealthCheckResult;
use LPWork\Health\HealthRunner;

use function sprintf;
use function str_starts_with;
use function substr;

/**
 * Handles the health check command console command.
 */
final readonly class HealthCheckCommand implements Command
{
    private const int DETAILS_LIMIT = 72;

    /**
     * @var array<string, string>
     */
    private const array DETAILS = [
        'php' => 'PHP requirements met.',
        'framework.modules' => 'Framework module registry is consistent.',
        'runtime.directories' => 'Runtime directories are readable and writable.',
        'development.tools' => 'Required development CLI tools are available.',
        'development.php_extensions' => 'Development PHP extensions are loaded.',
        'frontend.runtime' => 'Node, package manager, package.json, and Vite scripts are ready.',
        'frontend.quality' => 'TypeScript, linting, and formatting tooling are configured.',
        'frontend.testing' => 'Frontend unit and browser testing tooling is configured.',
        'frontend.build' => 'Frontend build output diagnostics are available.',
        'locks' => 'Atomic locks can be acquired and released.',
        'observability' => 'Metric collector accepts probe metrics.',
        'logging' => 'Default log channel accepts records.',
        'mail' => 'Default mail transport is resolvable.',
        'broadcasting' => 'Default broadcaster accepts probe events.',
        'notifications' => 'Notification channels are registered.',
        'scheduler' => 'Scheduler runner is resolvable.',
        'security' => 'CSRF tokens and signed URLs work.',
        'throttle' => 'Limiter can record configured attempts.',
        'translation' => 'Translator loaded locale and fallback support.',
        'views' => 'View factory renders framework views.',
        'console' => 'Required framework commands are registered.',
        'routing' => 'Router and URL generator resolve framework routes.',
    ];

    /**
     * Creates a new HealthCheckCommand instance.
     */
    public function __construct(
        private HealthRunner $health,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'health:check';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Run core runtime dependency health checks.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $report = $this->health->run();
        $checks = $report->checks();

        $this->messages->status($output, 'Health', $report->status(), $report->isHealthy());
        $output->writeln(sprintf(
            'Summary: %d ok, %d failed',
            count(array_filter($checks, static fn(HealthCheckResult $check): bool => $check->isHealthy())),
            count(array_filter($checks, static fn(HealthCheckResult $check): bool => !$check->isHealthy())),
        ));

        $this->messages->table($output, ['Area', 'Check', 'Status', 'Details'], array_map(
            static fn(HealthCheckResult $check): array => [
                self::area($check),
                self::check($check),
                self::status($check, $output),
                self::details($check),
            ],
            $checks,
        ));

        return $report->exitCode();
    }

    private static function area(HealthCheckResult $check): string
    {
        $name = $check->name();

        if ($name === 'php' || str_starts_with($name, 'runtime.')) {
            return 'Runtime';
        }

        if (str_starts_with($name, 'development.')) {
            return 'Development';
        }

        if (str_starts_with($name, 'frontend.')) {
            return 'Frontend';
        }

        if (in_array($name, ['storage', 'cache', 'database', 'queue'], true)) {
            return 'Services';
        }

        return 'Framework';
    }

    private static function status(HealthCheckResult $check, Output $output): string
    {
        return $output->format(
            $check->status(),
            $check->isHealthy() ? ConsoleColor::Green : ConsoleColor::Red,
        );
    }

    private static function check(HealthCheckResult $check): string
    {
        $name = $check->name();

        if (str_starts_with($name, 'development.')) {
            return substr($name, 12);
        }

        if (str_starts_with($name, 'runtime.')) {
            return substr($name, 8);
        }

        if (str_starts_with($name, 'frontend.')) {
            return substr($name, 9);
        }

        if (str_starts_with($name, 'framework.')) {
            return substr($name, 10);
        }

        return $name;
    }

    private static function details(HealthCheckResult $check): string
    {
        $name = $check->name();

        if (isset(self::DETAILS[$name]) && $check->isHealthy()) {
            return self::DETAILS[$name];
        }

        return self::limit($check->message());
    }

    private static function limit(string $value): string
    {
        if (strlen($value) <= self::DETAILS_LIMIT) {
            return $value;
        }

        return substr($value, 0, self::DETAILS_LIMIT - 3) . '...';
    }
}
