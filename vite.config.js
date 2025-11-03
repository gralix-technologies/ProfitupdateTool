import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react({
            include: "**/*.{jsx,tsx}",
            jsxRuntime: 'automatic',
            fastRefresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
    optimizeDeps: {
        include: ['react', 'react-dom', '@inertiajs/react'],
        force: true,
    },
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost',
            overlay: false,
        },
    },
});
