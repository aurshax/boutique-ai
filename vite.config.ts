import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/js/app/styles/main.css',
                'resources/js/app/main.ts',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // boutique-ai brend shriftlari (docs/ARCHITECTURE.md, dizayn)
                bunny('Bodoni Moda', { weights: [500, 600] }),
                bunny('Figtree', { weights: [400, 500, 600, 700] }),
                bunny('JetBrains Mono', { weights: [400, 500] }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Testlarda (vp test) Wayfinder generatsiyasi kerak emas — Sail ishlamasa ham testlar yuradi.
        ...(process.env.VITEST
            ? []
            : [
                  wayfinder({
                      formVariants: true,
                      // Mac'da PHP yo'q: wayfinder'ni Sail konteyneri ichida ishga tushiramiz
                      command: './vendor/bin/sail artisan wayfinder:generate',
                  }),
              ]),
    ]),
    server: {
        // IPv4 manzil: Node "localhost" ni [::1] ga aylantiradi, CSP esa IPv6 manzilni
        // ruxsat ro'yxatiga qo'sha olmaydi → skriptlar bloklanadi (docs/SETUP.md, Ma'lum xatolar).
        host: '127.0.0.1',
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
    test: {
        // Pure TS (shared/lib, entities, features/model) va composable testlari — DOM kerak emas.
        include: ['resources/js/**/*.test.ts'],
        environment: 'node',
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            // shadcn-vue (CLI generatsiya qiladi) — shared/ui/<komponent>/ papkalari; o'zimizning shared/ui/*.vue tekshiriladi
            'resources/js/shared/ui/*/**',
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
            'resources/js/shared/ui/*/**',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/js/app/styles/main.css',
        },
    },
});
