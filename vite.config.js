import { defineConfig } from 'vite';
import fg from 'fast-glob';
import laravel from 'laravel-vite-plugin';

const files = fg.sync([
    'resources/js/**/*.js',
    'resources/scss/**/*.scss',
]);

export default defineConfig({
    plugins: [
        laravel({
            input: files,
            refresh: true,
        }),
    ],
});
