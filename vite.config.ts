import { defineConfig } from 'vite';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

function viteInputEntries(): Record<string, string> {
    const output = execFileSync('php', ['lpwork', 'frontend:entries'], {
        cwd: import.meta.dirname,
        encoding: 'utf8',
        env: {
            ...process.env,
            LPWORK_BASE_PATH: import.meta.dirname,
        },
    });
    const entries: unknown = JSON.parse(output);

    if (entries === null || typeof entries !== 'object' || Array.isArray(entries)) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(entries)
            .filter((entry): entry is [string, string] => typeof entry[1] === 'string')
            .map(([name, sourcePath]) => [name, resolve(import.meta.dirname, sourcePath)]),
    );
}

export default defineConfig({
    appType: 'custom',
    publicDir: false,
    build: {
        assetsDir: 'assets',
        emptyOutDir: true,
        manifest: 'manifest.json',
        outDir: 'public/build',
        rollupOptions: {
            input: viteInputEntries(),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
    },
});
