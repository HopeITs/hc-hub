import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
    build: {
        rollupOptions: {
            output: {
                /**
                 * Split the always-on third-party code out of the app chunk.
                 *
                 * These libraries change only when we deliberately upgrade them,
                 * while app.js changes on most deploys. Bundled together, every
                 * deploy invalidated the entire ~1 MB download for returning
                 * users; separated, the vendor chunks stay in the browser cache
                 * across releases.
                 *
                 * amCharts is deliberately absent: it is already split into its
                 * own on-demand chunks by the dynamic imports in
                 * resources/js/charts/lazy-charts.js, and naming it here would
                 * pull it back into a chunk that loads eagerly.
                 */
                manualChunks: {
                    vendor: ['jquery', 'select2', 'flowbite'],
                    charts: ['chart.js'],
                    alerts: ['sweetalert2'],
                },
            },
        },
    },
});
