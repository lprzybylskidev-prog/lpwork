import { chromium } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';

const url = process.argv[2] || process.env.LPWORK_UI_URL || 'http://localhost:8080/';
const name = process.argv[3] || process.env.LPWORK_UI_NAME || slugFromUrl(url);
const selector = process.argv[4] || process.env.LPWORK_UI_SELECTOR || '';
const outputDir = resolve(process.cwd(), process.env.LPWORK_UI_OUTPUT_DIR || 'storage/playwright');
const executablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || '/usr/bin/chromium';

const viewports = [
    ['desktop', { width: 1440, height: 1000 }],
    ['mobile', { width: 390, height: 844 }],
];

await mkdir(outputDir, { recursive: true });

const browser = await chromium.launch({ executablePath });

try {
    for (const [label, viewport] of viewports) {
        const page = await browser.newPage({ viewport });

        await page.goto(url, { waitUntil: 'networkidle' });
        await prepareFrameworkView(
            page,
            selector === '#lp-debug-bar' || selector === '[data-lp-debug-bar]',
        );

        const pagePath = resolve(outputDir, `${name}-${label}.png`);
        await page.screenshot({ path: pagePath, fullPage: true });
        console.log(`${label} page screenshot: ${pagePath}`);

        if (selector !== '') {
            const target = page.locator(selector).first();

            if ((await target.count()) > 0 && (await target.isVisible())) {
                const targetPath = resolve(outputDir, `${name}-${label}-target.png`);
                await target.screenshot({ path: targetPath });
                console.log(`${label} target screenshot: ${targetPath}`);
            } else {
                console.log(`${label} target selector not visible: ${selector}`);
            }
        }

        await page.close();
    }
} finally {
    await browser.close();
}

async function prepareFrameworkView(page, inspectDebugBar) {
    const debugBar = page.locator('[data-lp-debug-bar]').first();

    if ((await debugBar.count()) === 0) {
        return;
    }

    if (!inspectDebugBar) {
        await debugBar.evaluate((element) => element.remove());

        return;
    }

    const dock = page.locator('[data-lp-debug-dock]').first();
    if ((await dock.count()) > 0 && (await dock.isVisible())) {
        await dock.click();
    }

    const metricsTab = page.locator('[data-lp-debug-tab="metrics"]').first();

    if ((await metricsTab.count()) > 0) {
        await metricsTab.click();
    }
}

function slugFromUrl(value) {
    try {
        const parsed = new URL(value);
        const slug = parsed.pathname
            .replace(/[^a-z0-9]+/gi, '-')
            .replace(/^-|-$/g, '')
            .toLowerCase();

        return slug === '' ? 'home' : slug;
    } catch {
        return 'framework-view';
    }
}
