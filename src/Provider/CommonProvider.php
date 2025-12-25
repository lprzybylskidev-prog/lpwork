<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Provider\Common\CacheModuleProvider;
use LPwork\Provider\Common\ConfigModuleProvider;
use LPwork\Provider\Common\ConsoleModuleProvider;
use LPwork\Provider\Common\DatabaseModuleProvider;
use LPwork\Provider\Common\ErrorHandlingModuleProvider;
use LPwork\Provider\Common\ErrorLogModuleProvider;
use LPwork\Provider\Common\EventModuleProvider;
use LPwork\Provider\Common\FilesystemModuleProvider;
use LPwork\Provider\Common\HttpClientModuleProvider;
use LPwork\Provider\Common\LoggingModuleProvider;
use LPwork\Provider\Common\MailModuleProvider;
use LPwork\Provider\Common\MigrationSeederModuleProvider;
use LPwork\Provider\Common\QueueModuleProvider;
use LPwork\Provider\Common\RedisModuleProvider;
use LPwork\Provider\Common\RoutingModuleProvider;
use LPwork\Provider\Common\SecurityModuleProvider;
use LPwork\Provider\Common\SessionModuleProvider;
use LPwork\Provider\Common\TimeModuleProvider;
use LPwork\Provider\Common\TranslationModuleProvider;
use LPwork\Provider\Common\ValidationModuleProvider;
use LPwork\Provider\Common\VersionModuleProvider;
use LPwork\Provider\Common\WebSocketModuleProvider;
use LPwork\Provider\Contract\ProviderInterface;

if (!\interface_exists(\Psr\Http\Client\ClientInterface::class)) {
    /** @psalm-suppress UnresolvableInclude */
    require_once \dirname(__DIR__, 2) . '/stubs/psr-http-client.php';
}

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
        (new ConfigModuleProvider())->register($containerBuilder);
        (new TimeModuleProvider())->register($containerBuilder);
        (new RedisModuleProvider())->register($containerBuilder);
        (new DatabaseModuleProvider())->register($containerBuilder);
        (new CacheModuleProvider())->register($containerBuilder);
        (new QueueModuleProvider())->register($containerBuilder);
        (new WebSocketModuleProvider())->register($containerBuilder);
        (new TranslationModuleProvider())->register($containerBuilder);
        (new HttpClientModuleProvider())->register($containerBuilder);
        (new ValidationModuleProvider())->register($containerBuilder);
        (new EventModuleProvider())->register($containerBuilder);
        (new MailModuleProvider())->register($containerBuilder);
        (new ErrorHandlingModuleProvider())->register($containerBuilder);
        (new SecurityModuleProvider())->register($containerBuilder);
        (new FilesystemModuleProvider())->register($containerBuilder);
        (new RoutingModuleProvider())->register($containerBuilder);
        (new MigrationSeederModuleProvider())->register($containerBuilder);
        (new ErrorLogModuleProvider())->register($containerBuilder);
        (new LoggingModuleProvider())->register($containerBuilder);
        (new SessionModuleProvider())->register($containerBuilder);
        (new VersionModuleProvider())->register($containerBuilder);
        (new ConsoleModuleProvider())->register($containerBuilder);
    }
}
