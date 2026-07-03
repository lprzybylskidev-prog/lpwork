import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        coverage: {
            reporter: ['text', 'html'],
            reportsDirectory: 'storage/frontend/coverage',
        },
        environment: 'node',
        globals: true,
        include: [
            'App/**/resources/frontend/**/*.test.ts',
            'App/Modules/*/tests/frontend/**/*.test.ts',
            'LPWork/Frontend/Resources/**/*.test.ts',
        ],
        passWithNoTests: true,
    },
});
