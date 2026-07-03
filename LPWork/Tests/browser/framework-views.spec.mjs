import { expect, test } from '@playwright/test';

test('framework-owned pages render without horizontal overflow', async ({ page }) => {
    for (const path of ['/', '/error/500', '/maintenance', '/__missing-debug-exception']) {
        await page.goto(path);

        await expect(page.locator('body.lp-ui-body')).toBeVisible();
        await expect(page.locator('link[rel="icon"]')).toHaveAttribute(
            'href',
            /\/favicon\.svg\?v=[a-f0-9]{12}/,
        );

        const hasHorizontalOverflow = await page.evaluate(
            () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
        );

        expect(hasHorizontalOverflow).toBe(false);
    }
});

test('welcome page presents the framework capability surface', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('.lp-ui-welcome-shell')).toBeVisible();
    await expect(page.locator('.lp-ui-welcome-orbit')).toBeVisible();
    await expect(page.locator('.lp-ui-welcome-mark img.lp-ui-logo')).toHaveAttribute(
        'src',
        /\/assets\/lpwork-logo\.svg\?v=[a-f0-9]{12}/,
    );
    await expect(page.locator('.lp-ui-welcome-mark small')).toContainText(
        /Home page|Strona główna/,
    );
    await expect(page.locator('.lp-ui-welcome-orbit-copy > .lp-ui-kicker')).toHaveCount(1);
    await expect(page.locator('.lp-ui-welcome-header')).toContainText(/LPWork 0\.1\.0-dev/);
    await expect(page.locator('.lp-ui-welcome-orbit-copy h1')).toContainText('LPWork');
    await expect(page.locator('.lp-ui-welcome-orbit-map')).toBeVisible();
    await expect(page.locator('#features-heading')).toContainText(
        /Framework modules|Moduły frameworka/,
    );
    await expect(page.locator('.lp-ui-chip-row')).not.toContainText(/LPWork 0\.1\.0-dev/);
    await expect(page.locator('.lp-ui-capability-list li')).toHaveCount(34);
    await expect(page.locator('.lp-ui-welcome-facts')).toHaveCount(0);
    await expect(page.locator('body')).not.toContainText(
        /Aplikacja działa|Application ready|Domyślna trasa|Default route/,
    );
});

test('status pages keep the diagnostic shell readable', async ({ page }) => {
    for (const [path, variant] of [
        ['/error/500', 'error'],
        ['/maintenance', 'maintenance'],
    ]) {
        await page.goto(path);

        await expect(page.locator('.lp-ui-status-page')).toBeVisible();
        await expect(page.locator('.lp-ui-status-brand img.lp-ui-logo')).toHaveAttribute(
            'src',
            /\/assets\/lpwork-logo\.svg\?v=[a-f0-9]{12}/,
        );
        await expect(page.locator(`.lp-ui-status-page--${variant}`)).toBeVisible();
        await expect(page.locator('.lp-ui-status-signal')).toHaveCount(0);
        await expect(page.locator('.lp-ui-status-details')).toHaveCount(0);
    }
});

test('debug exception page keeps diagnostics readable', async ({ page }) => {
    await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
    await page.goto('/__missing-debug-exception');

    await expect(page.locator('.lp-debug-page')).toBeVisible();
    await expect(page.locator('main .lp-debug-brand img.lp-ui-logo')).toHaveAttribute(
        'src',
        /\/assets\/lpwork-logo\.svg\?v=[a-f0-9]{12}/,
    );
    await expect(page.locator('.lp-debug-class-line')).toBeVisible();
    await expect(page.locator('.lp-debug-facts')).toContainText(/LPWork 0\.1\.0-dev/);
    await expect(page.locator('.lp-debug-fields').first()).toBeVisible();
    await expect(page.locator('[data-lp-copy-exception]')).toBeVisible();
    await page.locator('[data-lp-panel-tab="request"]').click();
    await expect(
        page.locator('[data-lp-panel="request"].is-active .lp-debug-data-head').first(),
    ).toBeVisible();

    const pageOverflow = await page.evaluate(() => ({
        horizontal: document.documentElement.scrollWidth > document.documentElement.clientWidth,
        vertical: document.documentElement.scrollHeight > document.documentElement.clientHeight,
        bodyOverflow: getComputedStyle(document.body).overflow,
        shellOverflow: getComputedStyle(document.querySelector('.lp-debug-shell')).overflow,
        railOverflow: getComputedStyle(document.querySelector('.lp-debug-rail')).overflow,
        stageOverflow: getComputedStyle(document.querySelector('.lp-debug-stage')).overflow,
        activePanelOverflow: getComputedStyle(document.querySelector('.lp-debug-panel.is-active'))
            .overflow,
        desktopRailStageTopOffset:
            window.innerWidth > 1080
                ? Math.abs(
                      document.querySelector('.lp-debug-rail').getBoundingClientRect().top -
                          document.querySelector('.lp-debug-stage').getBoundingClientRect().top,
                  )
                : 0,
    }));

    expect(pageOverflow.horizontal).toBe(false);
    expect(pageOverflow.vertical).toBe(true);
    expect(pageOverflow.bodyOverflow).toBe('visible');
    expect(pageOverflow.shellOverflow).toBe('visible');
    expect(pageOverflow.railOverflow).toBe('visible');
    expect(pageOverflow.stageOverflow).toBe('visible');
    expect(pageOverflow.activePanelOverflow).toBe('visible');
    expect(pageOverflow.desktopRailStageTopOffset).toBeLessThanOrEqual(1);

    await page.locator('[data-lp-copy-exception]').click();
    await expect(page.locator('[data-lp-copy-exception]')).toHaveText('Copied');

    const copied = await page.evaluate(() => navigator.clipboard.readText());

    expect(copied).toContain('# LPWork Debug Exception');
    expect(copied).toContain('## Exception');
    expect(copied).toContain('## Request');
    expect(copied).toContain('## Top Frames');
    expect(copied).toContain('NotFoundException');
});
