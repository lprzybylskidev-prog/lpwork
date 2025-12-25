<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Mail\MailManager;
use LPwork\Mail\MailerFactory;
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
            MailerFactory::class => \DI\autowire(MailerFactory::class),
            MailManager::class => \DI\autowire(MailManager::class),
            MailerInterface::class => \DI\factory(static function (
                MailManager $manager,
            ): MailerInterface {
                return $manager->default();
            }),
        ]);
    }
}
