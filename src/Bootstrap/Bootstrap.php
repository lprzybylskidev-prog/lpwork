<?php
declare(strict_types=1);

namespace LPwork\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use LPwork\Kernel\CliKernel;
use LPwork\Kernel\HttpKernel;
use LPwork\Provider\CliProvider;
use LPwork\Provider\CommonProvider;
use LPwork\Provider\Contract\ProviderInterface;
use LPwork\Provider\HttpProvider;
use LPwork\Runtime\RuntimeType;
use Config\AppProvider;

/**
 * Handles the initial bootstrapping of the LPwork framework.
 */
class Bootstrap
{
    /**
     * Boots the framework for the detected runtime context.
     *
     * @return void
     */
    public function run(): void
    {
        $runtimeType = $this->detectRuntimeType();
        $container = $this->buildContainer($runtimeType);

        $this->runKernel($runtimeType, $container);
    }

    /**
     * Determines the runtime environment type.
     *
     * @return RuntimeType
     */
    private function detectRuntimeType(): RuntimeType
    {
        if (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return RuntimeType::Cli;
        }

        return RuntimeType::Http;
    }

    /**
     * Builds a configured container for the given runtime.
     *
     * @param RuntimeType $runtimeType
     *
     * @return Container
     */
    private function buildContainer(RuntimeType $runtimeType): Container
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        foreach ($this->resolveProviders($runtimeType) as $provider) {
            $provider->register($containerBuilder);
        }

        return $containerBuilder->build();
    }

    /**
     * Returns providers required for the current runtime.
     *
     * @param RuntimeType $runtimeType
     *
     * @return array<int, ProviderInterface>
     */
    private function resolveProviders(RuntimeType $runtimeType): array
    {
        $providers = [
            new CommonProvider(),
            new AppProvider(),
        ];

        if ($runtimeType === RuntimeType::Cli) {
            $providers[] = new CliProvider();
        } else {
            $providers[] = new HttpProvider();
        }

        return $providers;
    }

    /**
     * Runs the kernel matching the runtime type.
     *
     * @param RuntimeType $runtimeType
     * @param Container   $container
     *
     * @return void
     */
    private function runKernel(RuntimeType $runtimeType, Container $container): void
    {
        if ($runtimeType === RuntimeType::Cli) {
            $container->get(CliKernel::class)->run();

            return;
        }

        $container->get(HttpKernel::class)->run();
    }
}
