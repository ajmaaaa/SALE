import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const rawHost = env.VITE_HOST || '';
    const viteHost = rawHost.replace(/^https?:\/\//, '').split(':')[0].trim() || 'localhost';

    return {
        server: {
            host: '0.0.0.0',
            port: 5173,
            cors: true,
            allowedHosts: true,
            hmr: {
                host: viteHost,
            },
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
    };
});
