<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Http\Session\SessionManager;
use LPwork\Security\Csrf\CsrfTokenProvider;
use LPwork\Security\SecurityConfiguration;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Registers security services such as CSRF and password hashing.
 */
final class SecurityModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CsrfTokenManagerInterface::class => \DI\factory(static function (
                SecurityConfiguration $config,
                SessionManager $sessionManager,
            ): CsrfTokenManagerInterface {
                $session = $sessionManager->current();
                $storage = new \LPwork\Security\Csrf\CsrfTokenSessionStorage($session);

                return new CsrfTokenManager(null, $storage);
            }),
            CsrfTokenProvider::class => \DI\autowire(CsrfTokenProvider::class),
            PasswordHasherFactoryInterface::class => \DI\factory(
                static function (): PasswordHasherFactoryInterface {
                    return new PasswordHasherFactory([
                        'default' => ['algorithm' => 'auto'],
                    ]);
                },
            ),
            UserPasswordHasherInterface::class => \DI\factory(static function (
                PasswordHasherFactoryInterface $factory,
            ): UserPasswordHasherInterface {
                return new UserPasswordHasher($factory);
            }),
        ]);
    }
}
