<?php
declare(strict_types=1);

namespace LPwork\Translation;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\Contract\CacheFactoryInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Translation\Contract\TranslationProviderInterface;
use LPwork\Translation\Contract\TranslatorFactoryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper to build translation configuration from env/config and create translators.
 */
final class TranslationProvider implements TranslationProviderInterface
{
    /**
     * @var TranslationConfiguration
     */
    private TranslationConfiguration $translationConfiguration;

    /**
     * @var CacheConfiguration
     */
    private CacheConfiguration $cacheConfiguration;

    /**
     * @var CacheFactoryInterface
     */
    private CacheFactoryInterface $cacheFactory;

    /**
     * @var RedisConnectionManagerInterface
     */
    private RedisConnectionManagerInterface $redisConnections;

    /**
     * @var DatabaseConnectionManagerInterface
     */
    private DatabaseConnectionManagerInterface $databaseConnections;

    /**
     * @var TranslatorFactoryInterface
     */
    private TranslatorFactoryInterface $translatorFactory;

    /**
     * @param TranslationConfiguration  $translationConfiguration
     * @param CacheConfiguration        $cacheConfiguration
     * @param CacheFactoryInterface     $cacheFactory
     * @param RedisConnectionManagerInterface $redisConnections
     * @param DatabaseConnectionManagerInterface  $databaseConnections
     * @param TranslatorFactoryInterface        $translatorFactory
     */
    public function __construct(
        TranslationConfiguration $translationConfiguration,
        CacheConfiguration $cacheConfiguration,
        CacheFactoryInterface $cacheFactory,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
        TranslatorFactoryInterface $translatorFactory,
    ) {
        $this->translationConfiguration = $translationConfiguration;
        $this->cacheConfiguration = $cacheConfiguration;
        $this->cacheFactory = $cacheFactory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
        $this->translatorFactory = $translatorFactory;
    }

    /**
     * Builds translator using configured locale, fallback, path and cache pool.
     *
     * @return TranslatorInterface
     */
    public function createTranslator(): TranslatorInterface
    {
        if ($this->translationConfiguration->cacheEnabled()) {
            $poolName = $this->translationConfiguration->cachePool();
            $pool = $this->cacheFactory->createPool(
                $poolName,
                $this->cacheConfiguration,
                $this->redisConnections,
                $this->databaseConnections,
            );
        } else {
            $pool = new ArrayAdapter(storeSerialized: false);
        }

        return $this->translatorFactory->create($this->translationConfiguration, $pool);
    }
}
