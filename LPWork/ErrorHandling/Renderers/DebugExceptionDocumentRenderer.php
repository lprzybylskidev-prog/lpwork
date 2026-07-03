<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\Frontend\FrameworkAssets;

/**
 * Renders debug exception document renderer output.
 */
final readonly class DebugExceptionDocumentRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(string $style, string $hero, string $workspace, string $script, string $debugBar = ''): string
    {
        return sprintf(
            <<<'HTML'
                <!doctype html>
                <html lang="en">
                <head>
                  <meta charset="utf-8">
                  <meta name="robots" content="noindex,nofollow">
                  <meta name="viewport" content="width=device-width, initial-scale=1">
                  <title>LPWork debug exception</title>
                  %s
                  %s
                  <style>%s</style>
                </head>
                <body class="lp-ui-body lp-debug-page">
                  <main class="lp-debug-shell">
                    %s
                    %s
                  </main>
                  %s
                  <script>%s</script>
                </body>
                </html>
                HTML,
            FrameworkAssets::faviconLink(),
            FrameworkAssets::stylesheetElement(),
            $style,
            $hero,
            $workspace,
            $debugBar,
            $script,
        );
    }
}
