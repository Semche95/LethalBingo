import {defineConfig} from 'vite'
import symfonyPlugin from 'vite-plugin-symfony'

export default defineConfig({
    plugins: [
        symfonyPlugin({
            stimulus: true,
        }),
    ],
    server: {
        // The Vite dev server is proxied through Caddy/FrankenPHP on the same
        // origin as the app (https://lethalbingo.localhost), so the browser never
        // talks to Vite directly. This is required because Web Workers cannot be
        // loaded cross-origin: the page (served by the PHP container) and the
        // assets (served by Vite on the host) must share the same origin.
        //
        // host '0.0.0.0' exposes Vite on all interfaces so the PHP container can
        // reach it through host.docker.internal, and `origin` makes Vite emit
        // same-origin absolute URLs (entry points, worker URLs, sound URLs).
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'https://lethalbingo.localhost',
        hmr: {
            // The HMR WebSocket must also go through the Caddy proxy (same
            // origin), otherwise hot reload silently stops working once the page
            // and the assets share the origin. The path is proxied alongside the
            // /build assets (see frankenphp/Caddyfile).
            host: 'lethalbingo.localhost',
            protocol: 'wss',
            path: '/@vite/hmr',
        },
        // Vite serves everything with permissive CORS as a safety net, but with
        // the reverse proxy all requests are same-origin anyway.
        cors: true,
    },
    build: {
        assetsInlineLimit: 0,
        rollupOptions: {
            input: {
                app: './assets/app.ts',
            },
        },
    },
})
