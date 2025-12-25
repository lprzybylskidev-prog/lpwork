<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\Exception\CacheConfigurationException;
use LPwork\Config\CachedConfigRepository;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Config\PhpConfigLoader;
use LPwork\Config\PhpConfigRepository;
use LPwork\Environment\Env;
use LPwork\Http\HttpConfiguration;
use LPwork\Mail\MailConfiguration;
use LPwork\Security\SecurityConfiguration;
use LPwork\Translation\TranslationConfiguration;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Registers environment handling and configuration repositories.
 */
final class ConfigModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            Env::class => \DI\factory(static function (): Env {
                /** @var array<string, string> $envVars */
                $envVars = $_ENV;

                return Env::fromArray($envVars);
            }),
            CacheConfiguration::class => \DI\factory(static function (
                Env $env,
            ): CacheConfiguration {
                $configDirectory = \dirname(__DIR__, 3) . '/config/configs';
                $loader = new PhpConfigLoader($env);
                $configs = $loader->loadDirectory($configDirectory);
                $cacheConfig = $configs['cache'] ?? [];

                return new CacheConfiguration((array) $cacheConfig);
            }),
            TranslationConfiguration::class => \DI\factory(static function (
                Env $env,
                CacheConfiguration $cacheConfiguration,
            ): TranslationConfiguration {
                $translationCache = $cacheConfiguration->translations();
                $translationConfig = [
                    'locale' => $env->getString('APP_LOCALE', 'en'),
                    'fallback_locale' => $env->getString('APP_FALLBACK_LOCALE', 'en'),
                    'path' => \dirname(__DIR__, 3) . '/config/lang',
                    'cache_enabled' => (bool) ($translationCache['enabled'] ?? true),
                    'cache_pool' => (string) ($translationCache['pool'] ?? 'filesystem'),
                    'cache_prefix' => (string) ($translationCache['prefix'] ?? 'translations:'),
                ];

                return new TranslationConfiguration($translationConfig);
            }),
            ConfigRepositoryInterface::class => \DI\factory(static function (
                Env $env,
                CacheConfiguration $cacheConfiguration,
            ): ConfigRepositoryInterface {
                $configDirectory = \dirname(__DIR__, 3) . '/config/configs';
                $loader = new PhpConfigLoader($env);
                $configs = $loader->loadDirectory($configDirectory);
                $configCache = $cacheConfiguration->configCache();
                $enabled = (bool) ($configCache['enabled'] ?? false);

                if ($enabled) {
                    $poolName = (string) ($configCache['pool'] ?? 'filesystem');
                    $key = (string) ($configCache['key'] ?? 'config:repository');

                    try {
                        $poolConfig = $cacheConfiguration->pool($poolName);
                        $driver = (string) ($poolConfig['driver'] ?? 'array');

                        if ($driver === 'array') {
                            $defaultTtl = (int) ($poolConfig['default_ttl'] ?? 0);
                            $ttlValue = $defaultTtl > 0 ? $defaultTtl : null;
                            $pool = new ArrayAdapter(
                                storeSerialized: false,
                                defaultLifetime: $ttlValue,
                            );

                            return new CachedConfigRepository($configs, $pool, $key);
                        }

                        if ($driver === 'filesystem') {
                            $defaultTtl = (int) ($poolConfig['default_ttl'] ?? 0);
                            $ttlValue = $defaultTtl > 0 ? $defaultTtl : null;
                            $namespace = (string) ($poolConfig['namespace'] ?? '');
                            $path =
                                (string) ($poolConfig['path'] ??
                                    \dirname(__DIR__, 3) . '/storage/cache');
                            $pool = new FilesystemAdapter($namespace, $ttlValue, $path);

                            return new CachedConfigRepository($configs, $pool, $key);
                        }
                    } catch (CacheConfigurationException) {
                        // fall through to non-cached repository
                    }
                }

                return new PhpConfigRepository($configs);
            }),
            HttpConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): HttpConfiguration {
                $httpConfig = $config->get('http', []);

                return new HttpConfiguration((array) $httpConfig);
            }),
            MailConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): MailConfiguration {
                $mailConfig = $config->get('mail', []);

                return new MailConfiguration((array) $mailConfig);
            }),
            SecurityConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): SecurityConfiguration {
                $securityConfig = $config->get('security', []);

                return new SecurityConfiguration((array) $securityConfig);
            }),
        ]);
    }
}
