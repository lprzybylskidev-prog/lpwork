<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Provider\Contract\ProviderInterface;

/**
 * Registers services shared between HTTP and CLI runtimes.
 */
class CommonProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        // Shared dependencies between runtimes will be registered here.
    }
}
