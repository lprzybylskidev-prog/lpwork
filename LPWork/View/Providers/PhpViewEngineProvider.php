<?php

declare(strict_types=1);

namespace LPWork\View\Providers;

use Closure;
use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\PhpViewEngineExtensions;
use LPWork\View\ViewNamespaceRegistry;

/**
 * Registers php view engine provider services with the framework container.
 */
abstract class PhpViewEngineProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        foreach ($this->viewEngines() as $engine) {
            $container->singleton(ViewEngine::class, $engine);
        }

        $extensions = $container->make(PhpViewEngineExtensions::class);

        if (!$extensions instanceof PhpViewEngineExtensions) {
            return;
        }

        foreach ($this->globals() as $name => $value) {
            $extensions->global($name, $value);
        }

        foreach ($this->functions() as $name => $function) {
            $extensions->function($name, $function);
        }

        $namespaces = $container->make(ViewNamespaceRegistry::class);

        if (!$namespaces instanceof ViewNamespaceRegistry) {
            return;
        }

        foreach ($this->viewNamespaces() as $namespace => $path) {
            $namespaces->add($namespace, $path);
        }
    }

    /**
     * @return list<class-string<ViewEngine>>
     */
    protected function viewEngines(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function globals(): array
    {
        return [];
    }

    /**
     * @return array<string, Closure>
     */
    protected function functions(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function viewNamespaces(): array
    {
        return [];
    }
}
