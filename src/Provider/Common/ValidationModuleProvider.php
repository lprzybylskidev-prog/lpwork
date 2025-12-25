<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Registers validation services.
 */
final class ValidationModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ValidatorBuilder::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
                TranslatorInterface $translator,
                CacheFactory $cacheFactory,
                CacheConfiguration $cacheConfiguration,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManagerInterface $databaseConnections,
            ): ValidatorBuilder {
                $settings = (array) $config->get('validation', []);
                $builder = \Symfony\Component\Validator\Validation::createValidatorBuilder();

                $builder->setTranslator($translator);
                $builder->setTranslationDomain(
                    (string) ($settings['translation_domain'] ?? 'validators'),
                );

                $mappingPaths = $settings['constraint_mapping_paths'] ?? [];
                if (\is_array($mappingPaths) && $mappingPaths !== []) {
                    foreach ($mappingPaths as $path) {
                        $builder->addXmlMapping((string) $path);
                        $builder->addYamlMapping((string) $path);
                    }
                }

                if ((bool) ($settings['cache_enabled'] ?? false)) {
                    $poolName = (string) ($settings['cache_pool'] ?? 'filesystem');
                    try {
                        $pool = $cacheFactory->createPool(
                            $poolName,
                            $cacheConfiguration,
                            $redisConnections,
                            $databaseConnections,
                        );
                        $builder->setMappingCache($pool);
                    } catch (\Throwable) {
                        // ignore cache setup failures
                    }
                }

                return $builder;
            }),
            ValidatorInterface::class => \DI\factory(static function (
                ValidatorBuilder $builder,
            ): ValidatorInterface {
                return $builder->getValidator();
            }),
        ]);
    }
}
