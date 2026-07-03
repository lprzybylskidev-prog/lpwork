<?php

declare(strict_types=1);

namespace LPWork\Mail\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\MailHealthCheck;
use LPWork\Logging\LogManager;
use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\MailManager;
use LPWork\Mail\MailMessageRenderer;
use LPWork\Mail\MailTransportFactory;
use LPWork\View\ViewFactory;

/**
 * Registers mail service provider services with the framework container.
 */
final class MailServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(MailManager::class, static function (Container $container): MailManager {
            $logManager = $container->make(LogManager::class);

            if (!$logManager instanceof LogManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(LogManager::class);
            }

            $logger = $logManager->channel(Config::getString('mail.logging.channel'));
            $templateView = self::templateView();
            $views = null;

            if ($templateView !== null) {
                $resolved = $container->make(ViewFactory::class);

                if (!$resolved instanceof ViewFactory) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewFactory::class);
                }

                $views = $resolved;
            }

            return new MailManager(
                config: Config::getArray('mail'),
                transportFactory: new MailTransportFactory(
                    logger: $logger,
                    appDebug: Config::getBool('app.debug'),
                    renderer: new MailMessageRenderer(views: $views, templateView: $templateView),
                ),
                logger: $logger,
                appDebug: Config::getBool('app.debug'),
            );
        });

        $container->singleton(MailTransport::class, static function (Container $container): MailTransport {
            $manager = $container->make(MailManager::class);

            if (!$manager instanceof MailManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MailManager::class);
            }

            return $manager->default();
        });

        $container->singleton(MailHealthCheck::class);
        $this->registerHealthCheck($container, MailHealthCheck::class);
    }

    private static function templateView(): ?string
    {
        $view = Config::get('mail.template_view', null);

        return is_string($view) && $view !== '' ? $view : null;
    }
}
