<?php
declare(strict_types=1);

namespace LPwork\Console\Provider;

use LPwork\Console\Command\MigrateCommand;
use LPwork\Console\Command\MigrateFreshCommand;
use LPwork\Console\Command\VersionCommand;
use LPwork\Console\Command\CacheWarmCommand;
use LPwork\Console\Command\CacheClearCommand;
use LPwork\Console\Command\DatabaseSeedCommand;
use LPwork\Console\Command\QueueWorkCommand;
use LPwork\Console\Command\QueueFlushCommand;
use LPwork\Console\Command\WebSocketServeCommand;
use LPwork\Console\Contract\CommandProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Provides framework built-in console commands.
 */
class BuiltinCommandProvider implements CommandProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getCommands(): array
    {
        return [
            $this->container->get(MigrateCommand::class),
            $this->container->get(MigrateFreshCommand::class),
            $this->container->get(VersionCommand::class),
            $this->container->get(CacheWarmCommand::class),
            $this->container->get(CacheClearCommand::class),
            $this->container->get(DatabaseSeedCommand::class),
            $this->container->get(QueueWorkCommand::class),
            $this->container->get(QueueFlushCommand::class),
            $this->container->get(WebSocketServeCommand::class),
        ];
    }
}
