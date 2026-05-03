import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: [
                'resources/css/filament.scss',
                'resources/js/filament.js',
            ],
            refresh: true,
            // This tells Laravel where to look for built assets in production
            // Defaults to public/build
            buildDirectory: 'build',
        }),
    ],
    base: mode === 'production' ? '/build/' : '/',
}));