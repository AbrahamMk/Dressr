import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    // Optional LAN access: set VITE_DEV_HOST=your.lan.ip in .env (clear it to revert to localhost)
    const { VITE_DEV_HOST: lanHost } = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        ...(lanHost
            ? {
                  server: {
                      host: '0.0.0.0',
                      hmr: { host: lanHost },
                      cors: true,
                  },
              }
            : {}),
    };
});
