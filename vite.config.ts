import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

/*
 * Vitest reads this file too. Its docs ("Config") say to branch on process.env.VITEST
 * for test-only configuration: tests need only React; the Laravel, Inertia, Wayfinder
 * and Tailwind plugins are for the app build and dev server (the Laravel plugin also
 * refuses to run as a "dev server" when CI is set, which broke CI).
 */
const isVitest = process.env.VITEST !== undefined;

export default defineConfig({
    plugins: lazyPlugins(() =>
        isVitest
            ? [react()]
            : [
                  laravel({
                      input: ['resources/css/app.css', 'resources/js/app.tsx'],
                      refresh: true,
                      fonts: [
                          google('IBM Plex Sans', {
                              alias: 'sans',
                              weights: [400, 500, 600, 700],
                              fallbacks: ['system-ui', 'sans-serif'],
                          }),
                          google('Barlow Condensed', {
                              alias: 'display',
                              weights: [600, 700, 800],
                              fallbacks: ['Arial Narrow', 'sans-serif'],
                          }),
                          google('IBM Plex Mono', {
                              alias: 'mono',
                              weights: [500, 600],
                              fallbacks: ['ui-monospace', 'monospace'],
                          }),
                      ],
                  }),
                  inertia(),
                  react(),
                  babel({
                      presets: [reactCompilerPreset()],
                  }),
                  tailwindcss(),
                  wayfinder({
                      formVariants: true,
                  }),
              ],
    ),
    resolve: {
        // Under Vitest, take the @/ alias from tsconfig.json (the Laravel plugin provides it otherwise).
        tsconfigPaths: isVitest,
    },
    test: {
        include: ['resources/js/**/*.test.{ts,tsx}'],
    },
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'design/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'design/**',
            '**/*.md',
            '.claude/**',
            'tests/Fixtures/**',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
