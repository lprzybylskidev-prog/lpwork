<?php

declare(strict_types=1);

namespace LPWork\Frontend\Providers;

use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use LPWork\Frontend\AssetEntryRegistry;

/**
 * Registers asset entrypoints provider services with the framework container.
 */
abstract class AssetEntrypointsProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $entries = $container->make(AssetEntryRegistry::class);

        if (!$entries instanceof AssetEntryRegistry) {
            return;
        }

        foreach ($this->assetEntries() as $name => $sourcePath) {
            $entries->add($name, $sourcePath);
        }
    }

    /**
     * @return array<string, string>
     */
    abstract protected function assetEntries(): array;
}
