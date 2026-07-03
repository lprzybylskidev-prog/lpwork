<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Renders framework brand renderer output.
 */
final readonly class FrameworkBrandRenderer
{
    /**
     * Creates a new FrameworkBrandRenderer instance.
     */
    public function __construct(
        private FrameworkAssetUrls $urls = new FrameworkAssetUrls(),
    ) {}

    /**
     * Performs the brand operation.
     */
    public function brand(string $label, string $class = 'lp-ui-framework-brand', bool $inlineLogo = false): string
    {
        return sprintf(
            '<div class="%s"><img class="lp-ui-logo" src="%s" alt="LPWork"><span><strong>LPWORK</strong><small>%s</small></span></div>',
            $this->escape($class),
            $this->escape($inlineLogo ? FrameworkAssets::logoDataUri() : $this->urls->logoUrl()),
            $this->escape($label),
        );
    }

    /**
     * Performs the favicon link operation.
     */
    public function faviconLink(): string
    {
        return sprintf(
            '<link rel="icon" href="%s" type="image/svg+xml">',
            $this->escape($this->urls->faviconUrl()),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
