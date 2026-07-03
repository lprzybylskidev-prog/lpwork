<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Renders framework page renderer output.
 */
final readonly class FrameworkPageRenderer
{
    /**
     * @param list<string> $meta
     */
    public function render(
        string $title,
        string $kicker,
        string $heading,
        string $body,
        ?string $status = null,
        array $meta = [],
    ): string {
        $metaHtml = $this->heroMeta($status, $meta);

        return sprintf(
            <<<'HTML'
                    <!doctype html>
                    <html lang="en">
                    <head>
                      <meta charset="utf-8">
                      <meta name="viewport" content="width=device-width, initial-scale=1">
                      <title>%s</title>
                      %s
                      %s
                    </head>
                    <body class="lp-ui-body">
                      <main class="lp-ui-shell">
                        <header class="lp-ui-hero">
                          <div class="lp-ui-topbar">
                            %s
                          </div>
                          <div class="lp-ui-hero-body">
                            %s
                            <h1 class="lp-ui-heading-xl">%s</h1>
                          </div>
                          %s
                        </header>
                        %s
                        %s
                      </main>
                    </body>
                    </html>
                HTML,
            $this->escape($title),
            FrameworkAssets::faviconLink(),
            FrameworkAssets::stylesheetElement(),
            FrameworkAssets::brand($kicker),
            $status === null ? '' : '<div class="lp-ui-status">' . $this->escape($status) . '</div>',
            $this->escape($heading),
            $metaHtml,
            $body,
            '',
        );
    }

    /**
     * Performs the error page operation.
     */
    public function errorPage(
        string $title,
        string $kicker,
        string $heading,
        int $statusCode,
        string $message,
        string $variant = 'error',
    ): string {
        $variantClass = preg_match('/^[a-z0-9-]+$/', $variant) === 1 ? $variant : 'error';

        return sprintf(
            <<<'HTML'
                    <!doctype html>
                    <html lang="en">
                    <head>
                      <meta charset="utf-8">
                      <meta name="viewport" content="width=device-width, initial-scale=1">
                      <title>%s</title>
                      %s
                      %s
                    </head>
                    <body class="lp-ui-body">
                      <main class="lp-ui-status-shell">
                        <article class="lp-ui-status-page lp-ui-status-page--%s">
                          <header class="lp-ui-status-brand">
                            %s
                            <span class="lp-ui-chip">HTTP %d</span>
                          </header>
                          <section class="lp-ui-status-main">
                            <div class="lp-ui-status-copy">
                              <p class="lp-ui-status-code">%d</p>
                              <h1>%s</h1>
                              <p>%s</p>
                            </div>
                          </section>
                        </article>
                      </main>
                    </body>
                    </html>
                HTML,
            $this->escape($title),
            FrameworkAssets::faviconLink(),
            FrameworkAssets::stylesheetElement(),
            $this->escape($variantClass),
            FrameworkAssets::brand($kicker),
            $statusCode,
            $statusCode,
            $this->escape($heading),
            $this->escape($message),
        );
    }

    /**
     * @param list<string> $meta
     */
    private function heroMeta(?string $status, array $meta): string
    {
        $items = '';

        if ($status !== null) {
            $items .= sprintf(
                '<div class="lp-ui-hero-meta-item"><span>Status</span><strong>%s</strong></div>',
                $this->escape($status),
            );
        }

        foreach ($meta as $index => $value) {
            $items .= sprintf(
                '<div class="lp-ui-hero-meta-item"><span>Detail %d</span><strong>%s</strong></div>',
                $index + 1,
                $this->escape($value),
            );
        }

        if ($items === '') {
            $items = '<div class="lp-ui-hero-meta-item"><span>Framework</span><strong>LPWork</strong></div>';
        }

        return '<div class="lp-ui-hero-meta">' . $items . '</div>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
