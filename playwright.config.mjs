import { defineConfig } from '@playwright/test';

const chromiumExecutable = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || '/usr/bin/chromium';

export default defineConfig({
    testDir: './LPWork/Tests/browser',
    outputDir: './storage/playwright/test-results',
    timeout: 30000,
    reporter: [['list']],
    use: {
        baseURL: process.env.LPWORK_BASE_URL || 'http://localhost:8080',
        browserName: 'chromium',
        launchOptions: {
            executablePath: chromiumExecutable,
        },
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [
        {
            name: 'desktop',
            use: {
                viewport: { width: 1440, height: 1000 },
            },
        },
        {
            name: 'mobile',
            use: {
                viewport: { width: 390, height: 844 },
                isMobile: true,
            },
        },
    ],
});
