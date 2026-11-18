import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Aset situs publik & admin (HTML/CSS/JS lama yang sudah dimigrasi)
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: [
                'resources/views/**/*.blade.php',
                'routes/**/*.php',
            ],
        }),
    ],
});
