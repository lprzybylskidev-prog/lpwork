<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Represents the framework assets framework component.
 */
final class FrameworkAssets
{
    public const string LOGO_RESOURCE = __DIR__ . '/Resources/assets/lpwork-logo.svg';

    public const string STYLESHEET_RESOURCE = __DIR__ . '/Resources/assets/framework.css';

    public const string LOGO_PUBLIC_PATH = '/assets/lpwork-logo.svg';

    public const string FAVICON_PUBLIC_PATH = '/favicon.svg';

    /**
     * Performs the brand operation.
     */
    public static function brand(string $label, string $class = 'lp-ui-framework-brand', bool $inlineLogo = false): string
    {
        return new FrameworkBrandRenderer()->brand($label, $class, $inlineLogo);
    }

    /**
     * Performs the favicon link operation.
     */
    public static function faviconLink(): string
    {
        return new FrameworkBrandRenderer()->faviconLink();
    }

    /**
     * Performs the stylesheet element operation.
     */
    public static function stylesheetElement(): string
    {
        return '<style>' . self::stylesheet() . '</style>';
    }

    /**
     * Performs the logo url operation.
     */
    public static function logoUrl(): string
    {
        return new FrameworkAssetUrls()->logoUrl();
    }

    /**
     * Performs the favicon url operation.
     */
    public static function faviconUrl(): string
    {
        return new FrameworkAssetUrls()->faviconUrl();
    }

    /**
     * Performs the logo svg operation.
     */
    public static function logoSvg(): string
    {
        $logo = file_get_contents(self::LOGO_RESOURCE);

        return is_string($logo) ? $logo : '';
    }

    /**
     * Performs the logo data uri operation.
     */
    public static function logoDataUri(): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::logoSvg());
    }

    /**
     * Performs the asset version operation.
     */
    public static function assetVersion(string $publicPath): string
    {
        $contents = match ($publicPath) {
            self::LOGO_PUBLIC_PATH, self::FAVICON_PUBLIC_PATH => self::logoSvg(),
            default => $publicPath,
        };

        return substr(hash('sha256', $contents), 0, 12);
    }

    /**
     * Performs the stylesheet operation.
     */
    public static function stylesheet(): string
    {
        $stylesheet = file_get_contents(self::STYLESHEET_RESOURCE);

        return is_string($stylesheet) ? $stylesheet : '';
    }

    /**
     * Performs the versioned public path operation.
     */
    public static function versionedPublicPath(string $publicPath): string
    {
        return new FrameworkAssetUrls()->versionedPublicPath($publicPath);
    }
}
