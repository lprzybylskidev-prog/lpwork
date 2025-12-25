<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Mail\MailManager;
use LPwork\Mail\MailerFactory;
use LPwork\Mail\Contract\MailerFactoryInterface;
use LPwork\Mail\Contract\MailManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Registers mailer services.
 */
final class MailModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            MailerFactoryInterface::class => \DI\autowire(MailerFactory::class),
            MailManager::class => \DI\autowire(MailManager::class),
            MailManagerInterface::class => \DI\get(MailManager::class),
            MailerInterface::class => \DI\factory(static function (
                MailManagerInterface $manager,
            ): MailerInterface {
                return $manager->default();
            }),
        ]);
    }
}
