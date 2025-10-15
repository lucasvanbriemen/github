import { defineConfig } from 'vite';
import fg from 'fast-glob';
import laravel from 'laravel-vite-plugin';
import { svelte } from '@sveltejs/vite-plugin-svelte';

const files = fg.sync([
    'resources/js/**/*.js',
    'resources/scss/**/*.scss',
]);

export default defineConfig({
    plugins: [
        svelte(),
        laravel({
            input: files,
            refresh: true,
        }),
    ],
});
