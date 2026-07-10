import {defineConfig} from 'vite'
import symfonyPlugin from 'vite-plugin-symfony'

export default defineConfig({
    plugins: [
        symfonyPlugin({
            stimulus: true,
        }),
    ],
    build: {
        assetsInlineLimit: 0,
        rollupOptions: {
            input: {
                app: './assets/app.ts',
            },
        },
    },
})
