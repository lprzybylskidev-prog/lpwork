import js from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: [
            'node_modules/',
            'public/build/',
            'storage/',
            'vendor/',
            'coverage/',
            '.phpstan.cache/',
            '.phpunit.cache/',
        ],
    },
    js.configs.recommended,
    ...tseslint.configs.recommendedTypeChecked,
    {
        files: ['App/**/*.ts', 'LPWork/Frontend/Resources/**/*.ts'],
        languageOptions: {
            globals: globals.browser,
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
    },
    {
        files: [
            '*.config.js',
            '*.config.mjs',
            '*.config.ts',
            'LPWork/Tools/**/*.mjs',
            'LPWork/Tests/browser/**/*.mjs',
        ],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
            },
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
    },
    {
        files: ['**/*.js', '**/*.mjs'],
        extends: [tseslint.configs.disableTypeChecked],
    },
);
