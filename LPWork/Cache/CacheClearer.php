<?php

declare(strict_types=1);

namespace LPWork\Cache;

/**
 * Represents the cache clearer framework component.
 */
final readonly class CacheClearer
{
    /**
     * Creates a new CacheClearer instance.
     */
    public function __construct(private CacheManager $manager) {}

    /**
     * @return list<string>
     */
    public function clear(?string $target = null): array
    {
        if ($target !== null) {
            $this->manager->store($target)->clear();

            return [$target];
        }

        $targets = $this->manager->storeNames();

        foreach ($targets as $store) {
            $this->manager->store($store)->clear();
        }

        return $targets;
    }
}
