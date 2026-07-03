<?php

declare(strict_types=1);

namespace LPWork\Security\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Events\EventRegistry;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\SecurityHealthCheck;
use LPWork\Kernels\Http\WebSecurityMiddlewareResolver;
use LPWork\Observability\MetricCollector;
use LPWork\Routing\RouteCollection;
use LPWork\Security\ApplicationKey;
use LPWork\Security\Contracts\Signer;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfMiddleware;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Security\Events\HttpSecurityDenied;
use LPWork\Security\Exceptions\InvalidApplicationKeyException;
use LPWork\Security\HmacSigner;
use LPWork\Security\Http\HttpSecurityMiddleware;
use LPWork\Security\Http\ValidateSignedUrlMiddleware;
use LPWork\Security\Listeners\RecordHttpSecurityDenied;
use LPWork\Security\SecurityConfig;
use LPWork\Security\SecurityConfigFactory;
use LPWork\Security\SecurityDebugCollector;
use LPWork\Security\SecurityDebugContextProvider;
use LPWork\Security\SignedUrl;
use LPWork\Security\SignedUrlValidator;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;
use LPWork\Url\Url;
use LPWork\Url\UrlGenerator;

/**
 * Registers security service provider services with the framework container.
 */
final class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(SecurityConfigFactory::class);
        $container->singleton(SecurityDebugCollector::class, static function (Container $container): SecurityDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new SecurityDebugCollector(metrics: $metrics);
        });
        $container->singleton(ApplicationKey::class, static fn(): ApplicationKey => ApplicationKey::fromString(Config::getString('security.app_key')));
        $container->singleton(Signer::class, HmacSigner::class);
        $container->singleton(SignedUrl::class, static function (Container $container): SignedUrl {
            $signer = $container->make(Signer::class);

            if (!$signer instanceof Signer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Signer::class);
            }

            try {
                $clock = $container->make(Clock::class);
            } catch (CannotResolveDependencyException) {
                $clock = new SystemClock();
            }

            return new SignedUrl($signer, $clock instanceof Clock ? $clock : new SystemClock());
        });
        $container->singleton(SignedUrlValidator::class);
        $container->bind(HttpSecurityMiddleware::class);
        $container->bind(ValidateSignedUrlMiddleware::class);
        $container->bind(CsrfMiddleware::class);
        $container->singleton(CsrfTokenManager::class);
        $container->singleton(WebSecurityMiddlewareResolver::class);

        $container->singleton(SecurityConfig::class, static function (Container $container): SecurityConfig {
            $factory = $container->make(SecurityConfigFactory::class);

            if (!$factory instanceof SecurityConfigFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SecurityConfigFactory::class);
            }

            return $factory->create(Config::getArray('security'));
        });

        $container->singleton(CsrfConfig::class, static function (Container $container): CsrfConfig {
            $security = $container->make(SecurityConfig::class);

            if (!$security instanceof SecurityConfig) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SecurityConfig::class);
            }

            return $security->csrf();
        });

        $this->configureSignedUrlGenerator($container);
        $this->registerDiagnostics($container);
        $container->singleton(SecurityHealthCheck::class);
        $this->registerHealthCheck($container, SecurityHealthCheck::class);
    }

    private function registerDiagnostics(Container $container): void
    {
        if ($container->has(EventRegistry::class)) {
            $registry = $container->make(EventRegistry::class);

            if ($registry instanceof EventRegistry) {
                $registry->add(HttpSecurityDenied::class, [RecordHttpSecurityDenied::class]);
            }
        }

        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): SecurityDebugContextProvider {
                $collector = $container->make(SecurityDebugCollector::class);

                if (!$collector instanceof SecurityDebugCollector) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(SecurityDebugCollector::class);
                }

                return new SecurityDebugContextProvider($collector);
            },
        );
    }

    private function configureSignedUrlGenerator(Container $container): void
    {
        try {
            $routes = $container->make(RouteCollection::class);
            $signedUrl = $container->make(SignedUrl::class);
        } catch (CannotResolveDependencyException|InvalidApplicationKeyException) {
            return;
        }

        if (!$routes instanceof RouteCollection || !$signedUrl instanceof SignedUrl) {
            return;
        }

        $container->singleton(UrlGenerator::class, static fn(): UrlGenerator => new UrlGenerator(
            routes: $routes,
            baseUrl: Config::getString('app.url'),
            signedUrl: $signedUrl,
        ));

        $urlGenerator = $container->make(UrlGenerator::class);

        if ($urlGenerator instanceof UrlGenerator) {
            Url::setGenerator($urlGenerator);
        }
    }
}
