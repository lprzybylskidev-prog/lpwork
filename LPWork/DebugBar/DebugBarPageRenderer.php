<?php

declare(strict_types=1);

namespace LPWork\DebugBar;

use LPWork\Observability\DiagnosticsSnapshot;
use LPWork\Observability\DiagnosticsSnapshotFactory;

/**
 * Renders debug bar page renderer output.
 */
final readonly class DebugBarPageRenderer
{
    /**
     * Creates a new DebugBarPageRenderer instance.
     */
    public function __construct(
        private DiagnosticsSnapshotFactory $snapshots,
        private DebugBarRenderer $renderer,
        private DebugBarRequestStore $store,
        private bool $enabled,
    ) {}

    /**
     * Builds or returns render current.
     */
    public function renderCurrent(): string
    {
        if (!$this->enabled) {
            return '';
        }

        return $this->render($this->snapshots->make());
    }

    /**
     * Renders this component into its output representation.
     */
    public function render(DiagnosticsSnapshot $snapshot): string
    {
        if (!$this->enabled) {
            return '';
        }

        $session = bin2hex(random_bytes(16));
        $id = bin2hex(random_bytes(12));
        $payload = $this->renderer->payload($snapshot, $id, $session);
        $this->store->put($session, $id, $payload);

        return $this->renderer->renderPayload($payload, collapsed: true);
    }
}
