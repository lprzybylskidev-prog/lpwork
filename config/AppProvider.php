<?php
declare(strict_types=1);

namespace Config;

use DI\ContainerBuilder;
use LPwork\Provider\Contract\ProviderInterface;

/**
 * Application-level provider loaded by the framework.
 */
class AppProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        // Application-specific services should be registered here.
    }
}
