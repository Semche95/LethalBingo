import {defineConfig} from 'vite'
import symfonyPlugin from 'vite-plugin-symfony'

export default defineConfig({
    plugins: [
        symfonyPlugin({
            stimulus: true,
        }),
    ],
    server: {
        // Proxied by Caddy on the app origin: Web Workers cannot load cross-origin.
        // 0.0.0.0 lets the PHP container reach Vite through host.docker.internal.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'https://lethalbingo.localhost',
        hmr: {
            // Through the Caddy proxy too (see frankenphp/Caddyfile)
            host: 'lethalbingo.localhost',
            protocol: 'wss',
            path: '/@vite/hmr',
        },
        cors: true,
    },
    build: {
        assetsInlineLimit: 0,
        rollupOptions: {
            input: {
                app: './assets/app.ts',
                // Loaded by a <link> tag, so that the page never shows without styles
                styles: './assets/app.css',
            },
        },
    },
})
