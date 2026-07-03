<?php

declare(strict_types=1);

namespace LPWork\Foundation\Providers;

use LPWork\Container\Container;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers file service provider services with the framework container.
 */
abstract class FileServiceProvider extends ServiceProvider
{
    /**
     * Creates a new FileServiceProvider instance.
     */
    public function __construct(
        protected readonly Application $app,
    ) {}

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        foreach ($this->loadFiles() as $file) {
            $this->load($this->app->basePath($file), $container);
        }
    }

    /**
     * @return list<string>
     */
    abstract protected function loadFiles(): array;
}
