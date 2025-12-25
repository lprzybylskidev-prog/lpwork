<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;
use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterFactoryInterface;
use LPwork\ErrorLog\ErrorIdProvider;
use LPwork\ErrorLog\ErrorLogConfiguration;
use LPwork\ErrorLog\ErrorLogWriterFactory;
use LPwork\ErrorLog\ErrorLogger;
use LPwork\Filesystem\Contract\FilesystemManagerInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;

/**
 * Registers error log facilities.
 */
final class ErrorLogModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ErrorLogConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): ErrorLogConfiguration {
                $errorLogConfig = $config->get('error_log', []);

                return new ErrorLogConfiguration((array) $errorLogConfig);
            }),
            ErrorLogWriterFactoryInterface::class => \DI\autowire(ErrorLogWriterFactory::class),
            ErrorLogWriterInterface::class => \DI\factory(static function (
                ErrorLogConfiguration $config,
                ErrorLogWriterFactoryInterface $factory,
                DatabaseConnectionManagerInterface $databaseConnections,
                RedisConnectionManagerInterface $redisConnections,
                FilesystemManagerInterface $filesystemManager,
            ): ErrorLogWriterInterface {
                return $factory->create(
                    $config,
                    $databaseConnections,
                    $redisConnections,
                    $filesystemManager,
                );
            }),
            ErrorIdProviderInterface::class => \DI\autowire(ErrorIdProvider::class),
            ErrorLoggerInterface::class => \DI\autowire(ErrorLogger::class),
        ]);
    }
}
