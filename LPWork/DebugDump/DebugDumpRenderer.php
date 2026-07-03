<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

use LPWork\Frontend\FrameworkAssets;

/**
 * Renders debug dump renderer output.
 */
final class DebugDumpRenderer
{
    /**
     * @param list<DebugDumpRecord> $records
     */
    public function overlay(array $records): string
    {
        if ($records === []) {
            return '';
        }

        return $this->styles(includeBase: true) . '<div class="lp-dump-overlay" data-lp-dump-overlay>'
            . '<div class="lp-dump-modal" role="dialog" aria-modal="false" aria-label="Debug dump">'
            . '<header class="lp-dump-topline">'
            . FrameworkAssets::brand('Debug dump', 'lp-ui-framework-brand lp-dump-brand', inlineLogo: true)
            . '<div class="lp-dump-actions"><span class="lp-ui-chip">' . count($records) . ' dump(s)</span>'
            . '<button class="lp-dump-close" type="button" aria-label="Hide debug dump" data-lp-dump-close>&times;</button></div>'
            . '</header>'
            . $this->recordList($records)
            . '</div>'
            . '<button class="lp-dump-dock" type="button" data-lp-dump-dock><span>LP</span> Dumps <b>' . count($records) . '</b></button>'
            . '</div>'
            . $this->script();
    }

    /**
     * Performs the page operation.
     */
    public function page(DebugDumpRecord $record, string $debugBar = ''): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>LPWork Debug Dump</title>' . FrameworkAssets::faviconLink() . $this->styles(includeBase: true) . '</head>'
            . '<body class="lp-ui-body lp-dump-page"><main class="lp-ui-shell lp-dump-page-shell">'
            . '<section class="lp-dump-modal lp-dump-modal-page" aria-label="Debug dump">'
            . $this->recordList([$record])
            . '</section></main>' . $debugBar . '</body></html>';
    }

    /**
     * @param list<DebugDumpRecord> $records
     */
    private function recordList(array $records): string
    {
        $html = '<div class="lp-dump-list">';

        foreach ($records as $record) {
            $label = $record->label();
            $header = $label === null
                ? ''
                : '<header><strong>' . $this->e($label) . '</strong><span title="Dump record id">#' . $this->e($record->id()) . '</span></header>';

            $html .= '<article class="lp-dump-record">'
                . $header
                . $this->node($record->root())
                . '</article>';
        }

        return $html . '</div>';
    }

    private function node(DebugDumpNode $node): string
    {
        $meta = $this->meta($node->meta());
        $name = $node->name();
        $key = $name === null
            ? '<span class="lp-dump-key lp-dump-key-empty"></span>'
            : '<span class="lp-dump-key">' . $this->e($name) . '</span>';
        $type = '<span class="lp-dump-type">' . $this->e($node->type()) . '</span>';

        if (!$node->hasChildren()) {
            $row = '<span class="lp-dump-row">'
                . $key
                . '<span class="lp-dump-value">'
                . '<code class="lp-dump-code">' . $this->e($node->summary()) . '</code>'
                . '<span class="lp-dump-details">' . $type . $meta . '</span>'
                . '</span>'
                . '</span>';

            return '<div class="lp-dump-node is-leaf">' . $row . '</div>';
        }

        $row = '<span class="lp-dump-row">'
            . '<span class="lp-dump-toggle" aria-hidden="true"></span>'
            . $key
            . '<span class="lp-dump-value">'
            . '<span class="lp-dump-branch-summary">' . $this->e($node->summary()) . '</span>'
            . '<span class="lp-dump-details">' . $type . $meta . '</span>'
            . '</span>'
            . '</span>';

        $html = '<details class="lp-dump-node is-branch"><summary>' . $row . '</summary><div class="lp-dump-children">';

        foreach ($node->children() as $child) {
            $html .= $this->node($child);
        }

        return $html . '</div></details>';
    }

    /**
     * @param array<string, string> $meta
     */
    private function meta(array $meta): string
    {
        if ($meta === []) {
            return '';
        }

        $items = [];

        foreach ($meta as $key => $value) {
            $items[] = $this->e($key) . ': ' . $this->e($value);
        }

        return '<span class="lp-dump-meta">' . implode(' / ', $items) . '</span>';
    }

    private function styles(bool $includeBase): string
    {
        return ($includeBase ? FrameworkAssets::stylesheetElement() : '') . <<<'HTML'
            <style>
              .lp-dump-overlay,
              .lp-dump-page {
                color: var(--lp-ui-text);
                font: 13px Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
              }

              .lp-dump-overlay {
                background: rgba(8, 13, 19, .74);
                backdrop-filter: blur(3px);
                display: grid;
                inset: 0;
                place-items: center;
                padding: 24px;
                position: fixed;
                z-index: 2147483647;
              }

              .lp-dump-overlay.is-collapsed {
                background: transparent;
                backdrop-filter: none;
                pointer-events: none;
              }

              .lp-dump-overlay:not(.is-collapsed) ~ #lp-debug-bar {
                display: none;
              }

              .lp-dump-page-shell {
                min-height: 100vh;
                place-items: center;
              }

              .lp-dump-modal {
                background: rgba(13, 20, 29, .98);
                border: 1px solid var(--lp-ui-line);
                border-radius: 8px;
                box-shadow: 0 24px 80px rgba(0, 0, 0, .48);
                max-height: min(76vh, 760px);
                overflow: hidden;
                width: min(1040px, 100%);
              }

              .lp-dump-modal-page {
                box-shadow: 0 24px 80px var(--lp-ui-shadow);
                max-height: none;
                width: min(1180px, 100%);
              }

              .lp-dump-overlay.is-collapsed .lp-dump-modal {
                display: none;
              }

              .lp-dump-topline {
                align-items: center;
                background: #0b1118;
                border-bottom: 1px solid var(--lp-ui-line);
                display: grid;
                gap: 18px;
                grid-template-columns: minmax(0, 1fr) auto;
                min-height: 58px;
                padding: 12px 14px;
                position: sticky;
                top: 0;
                z-index: 2;
              }

              .lp-dump-brand .lp-ui-logo {
                height: 28px;
                width: 28px;
              }

              .lp-dump-actions {
                align-items: center;
                display: flex;
                gap: 8px;
                justify-content: end;
              }

              .lp-dump-close {
                align-items: center;
                background: rgba(66, 136, 206, .15);
                border: 1px solid rgba(101, 169, 237, .42);
                border-radius: 6px;
                color: var(--lp-ui-blue-strong);
                cursor: pointer;
                display: inline-flex;
                font: inherit;
                font-size: 20px;
                font-weight: 800;
                height: 34px;
                justify-content: center;
                line-height: 1;
                width: 34px;
              }

              .lp-dump-close:hover {
                background: rgba(66, 136, 206, .24);
                color: #ffffff;
              }

              .lp-dump-dock {
                align-items: center;
                background: rgba(66, 136, 206, .15);
                border: 1px solid rgba(101, 169, 237, .42);
                border-radius: 6px;
                bottom: 12px;
                color: var(--lp-ui-blue-strong);
                cursor: pointer;
                display: none;
                font: inherit;
                font-weight: 800;
                gap: 7px;
                left: 12px;
                padding: 7px 10px;
                pointer-events: auto;
                position: fixed;
              }

              .lp-dump-dock:hover {
                background: rgba(66, 136, 206, .24);
                color: #ffffff;
              }

              .lp-dump-dock span {
                border: 1px solid currentColor;
                display: inline-block;
                font-weight: 800;
                line-height: 1;
                padding: 3px;
              }

              .lp-dump-dock b {
                color: inherit;
                font-size: 12px;
                font-weight: 900;
              }

              .lp-dump-overlay.is-collapsed .lp-dump-dock {
                display: inline-flex;
              }

              .lp-dump-overlay.is-collapsed:has(~ #lp-debug-bar) .lp-dump-dock {
                bottom: 54px;
              }

              .lp-dump-list {
                display: grid;
                gap: 12px;
                max-height: calc(min(76vh, 760px) - 59px);
                overflow: auto;
                padding: 16px;
              }

              .lp-dump-modal-page .lp-dump-list {
                max-height: none;
              }

              .lp-dump-record {
                background: #0a1017;
                border: 1px solid var(--lp-ui-line);
                border-radius: 7px;
                min-width: 0;
                overflow: hidden;
              }

              .lp-dump-record > header {
                align-items: center;
                background: #111a25;
                border-bottom: 1px solid var(--lp-ui-line);
                display: grid;
                gap: 12px;
                grid-template-columns: minmax(0, 1fr) auto;
                min-height: 38px;
                padding: 10px 12px;
              }

              .lp-dump-record > header span {
                color: var(--lp-ui-muted);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 12px;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
              }

              .lp-dump-record > header strong {
                color: #d8e4f3;
                font-size: 13px;
                font-weight: 900;
                min-width: 0;
                overflow-wrap: anywhere;
              }

              .lp-dump-node {
                margin: 0;
                min-width: 0;
              }

              .lp-dump-node + .lp-dump-node {
                border-top: 1px solid rgba(255, 255, 255, .052);
              }

              .lp-dump-node summary {
                cursor: pointer;
                list-style: none;
              }

              .lp-dump-node summary::-webkit-details-marker {
                display: none;
              }

              .lp-dump-toggle {
                align-self: center;
                border-bottom: 2px solid var(--lp-ui-blue-strong);
                border-right: 2px solid var(--lp-ui-blue-strong);
                grid-column: 1;
                height: 7px;
                justify-self: center;
                opacity: .9;
                transform: rotate(-45deg);
                transition: transform .12s ease;
                width: 7px;
              }

              .lp-dump-node[open] > summary .lp-dump-toggle {
                transform: rotate(45deg);
              }

              .lp-dump-row {
                align-items: center;
                display: grid;
                gap: 8px;
                grid-template-columns: 18px minmax(120px, 220px) minmax(0, 1fr);
                min-width: 0;
                padding: 7px 12px;
              }

              .lp-dump-record > .lp-dump-node > .lp-dump-row,
              .lp-dump-record > details.lp-dump-node > summary > .lp-dump-row {
                border-top: 0;
              }

              .lp-dump-node.is-branch > summary > .lp-dump-row {
                background: #0c131b;
                border-left: 2px solid rgba(101, 169, 237, .26);
              }

              .lp-dump-node.is-branch > summary:hover > .lp-dump-row {
                background: #101923;
              }

              .lp-dump-node.is-branch[open] > summary > .lp-dump-row {
                background: #0d141d;
                border-bottom: 1px solid rgba(255, 255, 255, .045);
              }

              .lp-dump-node.is-leaf > .lp-dump-row {
                background: #0b1118;
              }

              .lp-dump-key {
                color: #d8e4f3;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 12px;
                font-weight: 900;
                grid-column: 2;
                line-height: 1.35;
                min-width: 0;
                overflow-wrap: anywhere;
              }

              .lp-dump-node.is-leaf .lp-dump-key {
                color: #c7d4e3;
                font-weight: 800;
              }

              .lp-dump-key-empty::before {
                color: var(--lp-ui-muted);
                content: "root";
                font-weight: 700;
                opacity: .42;
              }

              .lp-dump-type {
                background: rgba(66, 136, 206, .1);
                border: 1px solid rgba(101, 169, 237, .2);
                border-radius: 999px;
                color: #85b9eb;
                display: inline-flex;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 11px;
                font-weight: 800;
                justify-self: start;
                line-height: 1;
                min-width: 0;
                overflow-wrap: anywhere;
                padding: 4px 7px;
              }

              .lp-dump-value {
                align-items: baseline;
                display: flex;
                flex-wrap: wrap;
                gap: 6px 9px;
                grid-column: 3;
                min-width: 0;
              }

              .lp-dump-branch-summary {
                color: #f0f6ff;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.35;
                min-width: 0;
                overflow-wrap: anywhere;
              }

              .lp-dump-code {
                background: transparent;
                border: 0;
                border-radius: 0;
                color: #e7eef8;
                display: inline;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 12px;
                line-height: 1.35;
                max-width: 100%;
                overflow: auto;
                overflow-wrap: anywhere;
                padding: 0;
                white-space: pre-wrap;
              }

              .lp-dump-node.is-leaf .lp-dump-code {
                background: rgba(8, 16, 24, .52);
                border: 1px solid rgba(255, 255, 255, .055);
                border-radius: 4px;
                padding: 3px 5px;
              }

              .lp-dump-details {
                align-items: center;
                display: flex;
                flex-wrap: wrap;
                gap: 7px;
              }

              .lp-dump-meta {
                color: var(--lp-ui-muted);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 11px;
              }

              .lp-dump-children {
                border-left: 1px solid rgba(101, 169, 237, .28);
                margin-left: 20px;
              }

              @media (max-width: 680px) {
                .lp-dump-overlay,
                .lp-dump-page-shell {
                  padding: 12px;
                }

                .lp-dump-modal {
                  max-height: calc(100vh - 24px);
                }

                .lp-dump-topline {
                  gap: 10px;
                  grid-template-columns: minmax(0, 1fr) auto;
                }

                .lp-dump-row {
                  gap: 7px;
                  grid-template-columns: 16px minmax(154px, 48%) minmax(0, 1fr);
                  padding-left: 10px;
                  padding-right: 10px;
                }

                .lp-dump-record > header {
                  grid-template-columns: 1fr;
                }

                .lp-dump-actions {
                  justify-content: end;
                }

                .lp-dump-list {
                  gap: 10px;
                  padding: 10px;
                }

                .lp-dump-children {
                  margin-left: 14px;
                }
              }
            </style>
            HTML;
    }

    private function script(): string
    {
        return <<<'HTML'
            <script>
              document.addEventListener('click', function (event) {
                var target = event.target;
                if (!target || !target.closest) {
                  return;
                }

                var trigger = target.closest('[data-lp-dump-close], [data-lp-dump-dock]');
                if (!trigger) {
                  return;
                }

                var overlay = trigger.closest('[data-lp-dump-overlay]');
                if (!overlay) {
                  return;
                }

                if (trigger.matches('[data-lp-dump-close]')) {
                  overlay.classList.add('is-collapsed');
                  return;
                }

                overlay.classList.remove('is-collapsed');
              });
            </script>
            HTML;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
