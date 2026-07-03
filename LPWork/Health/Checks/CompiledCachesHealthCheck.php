<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Foundation\Contracts\ReadableCompiledCache;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use Throwable;

/**
 * Represents the compiled caches health check framework component.
 */
final readonly class CompiledCachesHealthCheck implements HealthCheck
{
    /**
     * Creates a new CompiledCachesHealthCheck instance.
     */
    public function __construct(
        private CompiledCacheRegistry $caches,
        private Filesystem $files,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'compiled_caches';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $failures = [];
        $checked = 0;

        foreach ($this->caches->all() as $cache) {
            if (!$cache instanceof ReadableCompiledCache || !$cache->exists()) {
                continue;
            }

            $checked++;
            $path = $cache->path();

            if (!$this->files->isReadable($path)) {
                $failures[] = $cache->label() . ' is not readable.';

                continue;
            }

            try {
                $contents = include $path;
            } catch (Throwable) {
                $failures[] = $cache->label() . ' cannot be loaded.';

                continue;
            }

            if (!is_array($contents)) {
                $failures[] = $cache->label() . ' did not return an array.';
            }
        }

        if ($failures !== []) {
            return HealthCheckResult::unhealthy($this->name(), implode(' ', $failures));
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Readable compiled cache files checked: %d.', $checked));
    }
}
