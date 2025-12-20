<?php
declare(strict_types=1);

namespace LPwork\Provider\Contract;

use DI\ContainerBuilder;

/**
 * Represents a container provider that registers dependencies for a runtime scope.
 */
interface ProviderInterface
{
    /**
     * Registers service definitions on the container builder.
     *
     * @param ContainerBuilder $containerBuilder
     *
     * @return void
     */
    public function register(ContainerBuilder $containerBuilder): void;
}
