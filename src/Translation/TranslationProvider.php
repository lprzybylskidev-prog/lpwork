<?php
declare(strict_types=1);

namespace LPwork\Translation;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Redis\RedisConnectionManager;
use LPwork\Database\DatabaseConnectionManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper to build translation configuration from env/config and create translators.
 */
final class TranslationProvider
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
     * @var CacheFactory
     */
    private CacheFactory $cacheFactory;

    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $redisConnections;

    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $databaseConnections;

    /**
     * @var TranslatorFactory
     */
    private TranslatorFactory $translatorFactory;

    /**
     * @param TranslationConfiguration  $translationConfiguration
     * @param CacheConfiguration        $cacheConfiguration
     * @param CacheFactory              $cacheFactory
     * @param RedisConnectionManager    $redisConnections
     * @param DatabaseConnectionManager $databaseConnections
     * @param TranslatorFactory         $translatorFactory
     */
    public function __construct(
        TranslationConfiguration $translationConfiguration,
        CacheConfiguration $cacheConfiguration,
        CacheFactory $cacheFactory,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
        TranslatorFactory $translatorFactory,
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

        return $this->translatorFactory->create(
            $this->translationConfiguration,
            $pool,
        );
    }
}
