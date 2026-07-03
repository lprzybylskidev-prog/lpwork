<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Represents the framework asset urls framework component.
 */
final readonly class FrameworkAssetUrls
{
    /**
     * Performs the logo url operation.
     */
    public function logoUrl(): string
    {
        return $this->versionedPublicPath(FrameworkAssets::LOGO_PUBLIC_PATH);
    }

    /**
     * Performs the favicon url operation.
     */
    public function faviconUrl(): string
    {
        return $this->versionedPublicPath(FrameworkAssets::FAVICON_PUBLIC_PATH);
    }

    /**
     * Performs the versioned public path operation.
     */
    public function versionedPublicPath(string $publicPath): string
    {
        return $publicPath . '?v=' . FrameworkAssets::assetVersion($publicPath);
    }
}
