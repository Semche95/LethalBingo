import { defineConfig } from 'vitest/config'
import symfonyPlugin from 'vite-plugin-symfony'

export default defineConfig({
    plugins: [
        symfonyPlugin({
            stimulus: true,
        }),
    ],
    test: {
        environment: 'jsdom',
        include: ['assets/tests/**/*.{test,spec}.ts'],
        globals: true,
    },
})
