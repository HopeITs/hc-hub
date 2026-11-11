import tailwindcss from '@tailwindcss/vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
            ],
            // Module views and routes reload the page in dev like resources/views does.
            refresh: [...refreshPaths, 'modules/*/resources/views/**', 'modules/*/routes/**'],
        }),
        tailwindcss(),
    ]),
    build: {
        // apexcharts and xlsx are single vendor libraries, already split into their own lazy chunks.
        chunkSizeWarningLimit: 600,
    },
    server: {
        // An IPv4 host: browsers cannot match an IPv6 literal such as [::1] in
        // a Content-Security-Policy, so the dev server would be blocked.
        host: '127.0.0.1',
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
