import { execFileSync } from 'node:child_process';

import { expect, test } from '@playwright/test';

const fakeWebSocketScript = `
  <script>
    window.__lpworkDebugSockets = [];

    class FakeWebSocket extends EventTarget {
      static CONNECTING = 0;
      static OPEN = 1;
      static CLOSING = 2;
      static CLOSED = 3;

      constructor(url) {
        super();
        this.url = url;
        window.__lpworkDebugSockets.push(this);
      }

      emitMessage(data) {
        this.dispatchEvent(new MessageEvent('message', { data }));
      }
    }

    window.WebSocket = FakeWebSocket;
  </script>
`;

function debugbarHtml() {
    const html = execFileSync(
        'php',
        [
            '-r',
            `
    require 'vendor/autoload.php';

    echo '<!doctype html><html><body>';
    echo (new LPWork\\DebugBar\\DebugBarRenderer())->render(new LPWork\\Observability\\DiagnosticsSnapshot(
        groups: ['Request' => ['Method' => 'GET', 'Path' => '/debugbar-realtime']],
        metrics: [new LPWork\\Observability\\Metric('http.request.duration', 1.0, 'ms', ['status' => 200], 0.0, 1024)],
        logs: [],
    ));
    echo '</body></html>';
  `,
        ],
        { encoding: 'utf8' },
    );

    return html.replace('<body>', `<body>${fakeWebSocketScript}`);
}

test('debugbar keeps an opened websocket record open while realtime events arrive', async ({
    page,
}) => {
    await page.route('http://debugbar.test/', async (route) => {
        await route.fulfill({
            body: debugbarHtml(),
            contentType: 'text/html',
        });
    });

    await page.goto('http://debugbar.test/');
    await page.evaluate(() => {
        window.__lpworkAppSocket = new WebSocket('ws://example.test/realtime');
    });

    await page.locator('[data-lp-debug-tab="websocket"]').click();

    const firstRecord = page.locator('[data-lp-debug-panel="websocket"] .lp-debug-record').first();
    await firstRecord.locator('summary').click();
    await expect(firstRecord).toHaveJSProperty('open', true);

    await page.evaluate(() => {
        window.__lpworkAppSocket.emitMessage('still here');
    });

    await expect(firstRecord).toHaveJSProperty('open', true);
});
