<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;
use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\ErrorIdProvider;
use LPwork\ErrorLog\ErrorLogConfiguration;
use LPwork\ErrorLog\ErrorLogWriterFactory;
use LPwork\ErrorLog\ErrorLogger;
use LPwork\Filesystem\FilesystemManager;
use LPwork\Redis\RedisConnectionManager;

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
            ErrorLogWriterInterface::class => \DI\factory(static function (
                ErrorLogConfiguration $config,
                ErrorLogWriterFactory $factory,
                DatabaseConnectionManager $databaseConnections,
                RedisConnectionManager $redisConnections,
                FilesystemManager $filesystemManager,
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
