<?php

declare(strict_types=1);

namespace LPWork\Foundation\Modules;

use function count;
use function explode;
use function implode;

use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\Exceptions\InvalidModuleNameException;

use function preg_match;
use function str_replace;
use function trim;

/**
 * Resolves module path resolver values into runtime objects.
 */
final readonly class ModulePathResolver
{
    /**
     * Creates a new ModulePathResolver instance.
     */
    public function __construct(
        private Application $app,
        private string $rootPath = 'App/Modules',
        private string $rootNamespace = 'App\\Modules',
    ) {}

    /**
     * Resolves configured input into a runtime value.
     */
    public function resolve(string $name): ResolvedModule
    {
        $segments = $this->segments($name);
        $moduleName = $segments[count($segments) - 1];
        $relativeModulePath = trim($this->rootPath, '/') . '/' . implode('/', $segments);
        $moduleNamespace = trim($this->rootNamespace, '\\') . '\\' . implode('\\', $segments);
        $providerClass = $moduleName . 'ServiceProvider';

        return new ResolvedModule(
            name: $moduleName,
            path: $this->app->basePath($relativeModulePath),
            namespace: $moduleNamespace,
            serviceProviderClass: $moduleNamespace . '\\' . $providerClass,
            serviceProviderPath: $this->app->basePath($relativeModulePath . '/' . $providerClass . '.php'),
        );
    }

    /**
     * @return non-empty-list<string>
     */
    private function segments(string $name): array
    {
        $name = trim(str_replace('\\', '/', $name), "/ \t\n\r\0\x0B");

        if ($name === '') {
            throw InvalidModuleNameException::empty();
        }

        $segments = [];

        foreach (explode('/', $name) as $segment) {
            if ($segment === '') {
                continue;
            }

            $segment = ucfirst($segment);

            if (preg_match('/^[A-Z][A-Za-z0-9_]*$/', $segment) !== 1) {
                throw InvalidModuleNameException::invalid($name);
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            throw InvalidModuleNameException::empty();
        }

        return $segments;
    }
}
