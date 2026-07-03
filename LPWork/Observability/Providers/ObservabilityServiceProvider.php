<?php

declare(strict_types=1);

namespace LPWork\Observability\Providers;

use InvalidArgumentException;
use LPWork\Cache\CacheDebugCollector;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseDebugCollector;
use LPWork\Events\EventDebugCollector;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\ObservabilityHealthCheck;
use LPWork\Logging\Contracts\Logger;
use LPWork\Observability\DiagnosticsCollector;
use LPWork\Observability\DiagnosticsSnapshotFactory;
use LPWork\Observability\MetricCollector;
use LPWork\Observability\MetricReporterFactory;
use LPWork\Observability\RequestDiagnosticsResetter;
use LPWork\Queue\QueueDebugCollector;
use LPWork\Schedule\ScheduleDebugCollector;
use LPWork\Security\SecurityDebugCollector;
use LPWork\Throttle\ThrottleDebugCollector;
use LPWork\View\ViewDebugCollector;
use Throwable;

/**
 * Registers observability service provider services with the framework container.
 */
final class ObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(DiagnosticsCollector::class);
        $container->singleton(MetricCollector::class, static function (Container $container): MetricCollector {
            $app = $container->make(Application::class);
            $logger = self::optional($container, Logger::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            try {
                $config = Config::getArray('observability');
            } catch (Throwable) {
                $config = [
                    'metrics' => [
                        'limit' => 100,
                        'reporters' => [
                            'null' => ['driver' => 'null'],
                        ],
                        'enabled_reporters' => ['null'],
                    ],
                ];
            }
            $reader = new ArrayConfigReader(
                config: $config,
                missingException: static fn(string $key): InvalidArgumentException => new InvalidArgumentException("Missing observability config [{$key}]."),
                invalidException: static fn(string $key): InvalidArgumentException => new InvalidArgumentException("Invalid observability config [{$key}]."),
            );
            $metrics = $reader->array('metrics', 'metrics');
            $metricsReader = new ArrayConfigReader(
                config: $metrics,
                missingException: static fn(string $key): InvalidArgumentException => new InvalidArgumentException("Missing metrics config [{$key}]."),
                invalidException: static fn(string $key): InvalidArgumentException => new InvalidArgumentException("Invalid metrics config [{$key}]."),
            );
            $factory = new MetricReporterFactory($app, $logger instanceof Logger ? $logger : null);
            $reporters = [];
            $configuredReporters = $metricsReader->arrayMap('reporters', 'metrics.reporters');

            foreach ($metricsReader->stringList('enabled_reporters', 'metrics.enabled_reporters') as $name) {
                if (isset($configuredReporters[$name])) {
                    $reporters[] = $factory->create($configuredReporters[$name], "metrics.reporters.{$name}");
                }
            }

            return new MetricCollector($reporters, $metricsReader->int('limit', 'metrics.limit'));
        });
        $container->singleton(ObservabilityHealthCheck::class);
        $this->registerHealthCheck($container, ObservabilityHealthCheck::class);
        $container->singleton(DiagnosticsSnapshotFactory::class);
        $container->singleton(RequestDiagnosticsResetter::class, static function (Container $container): RequestDiagnosticsResetter {
            $diagnostics = $container->make(DiagnosticsCollector::class);
            $metrics = $container->make(MetricCollector::class);

            if (!$diagnostics instanceof DiagnosticsCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DiagnosticsCollector::class);
            }

            if (!$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new RequestDiagnosticsResetter(
                diagnostics: $diagnostics,
                metrics: $metrics,
                cache: self::optionalCollector($container, CacheDebugCollector::class),
                database: self::optionalCollector($container, DatabaseDebugCollector::class),
                events: self::optionalCollector($container, EventDebugCollector::class),
                queue: self::optionalCollector($container, QueueDebugCollector::class),
                schedule: self::optionalCollector($container, ScheduleDebugCollector::class),
                security: self::optionalCollector($container, SecurityDebugCollector::class),
                throttle: self::optionalCollector($container, ThrottleDebugCollector::class),
                views: self::optionalCollector($container, ViewDebugCollector::class),
            );
        });
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    private static function optionalCollector(Container $container, string $class): ?object
    {
        $object = parent::optional($container, $class);

        return $object instanceof $class ? $object : null;
    }
}
