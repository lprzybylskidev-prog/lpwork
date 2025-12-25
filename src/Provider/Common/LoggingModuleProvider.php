<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use Carbon\CarbonImmutable;
use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Logging\LogConfiguration;
use LPwork\Logging\LogFactory;
use LPwork\Redis\RedisConnectionManager;
use Psr\Log\LoggerInterface;

/**
 * Registers logging services.
 */
final class LoggingModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            LogConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): LogConfiguration {
                $loggingConfig = $config->get('logging', []);

                return new LogConfiguration((array) $loggingConfig);
            }),
            LogFactory::class => \DI\autowire(LogFactory::class),
            LoggerInterface::class => \DI\factory(static function (
                LogFactory $factory,
                LogConfiguration $configuration,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManager $databaseConnections,
                CarbonImmutable $now,
            ): LoggerInterface {
                return $factory->createDefault(
                    $configuration,
                    $redisConnections,
                    $databaseConnections,
                    $now,
                );
            }),
        ]);
    }
}
