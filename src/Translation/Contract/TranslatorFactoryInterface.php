<?php
declare(strict_types=1);

namespace LPwork\Translation\Contract;

use LPwork\Translation\TranslationConfiguration;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contract for creating translators.
 */
interface TranslatorFactoryInterface
{
    /**
     * @param TranslationConfiguration $config
     * @param CacheItemPoolInterface   $pool
     *
     * @return TranslatorInterface
     */
    public function create(
        TranslationConfiguration $config,
        CacheItemPoolInterface $pool,
    ): TranslatorInterface;
}
