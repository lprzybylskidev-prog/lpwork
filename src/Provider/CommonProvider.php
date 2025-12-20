<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Config\PhpConfigLoader;
use LPwork\Config\PhpConfigRepository;
use LPwork\Environment\Env;
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
        $containerBuilder->addDefinitions([
            Env::class => \DI\factory(static function (): Env {
                /** @var array<string, string> $envVars */
                $envVars = $_ENV;

                return Env::fromArray($envVars);
            }),
            ConfigRepositoryInterface::class => \DI\factory(static function (
                Env $env,
            ): ConfigRepositoryInterface {
                $configDirectory = \dirname(__DIR__, 2) . "/config/configs";
                $loader = new PhpConfigLoader($env);
                $configs = $loader->loadDirectory($configDirectory);

                return new PhpConfigRepository($configs);
            }),
        ]);
    }
}
