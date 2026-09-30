import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
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
    server: {
        // Разрешаем доступ к серверу извне контейнера
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Настраиваем HMR для корректной работы через Docker
        hmr: {
            host: 'localhost', // Или IP-адрес, если вы используете удаленный сервер
        },
        // Иногда требуется для корректного отслеживания изменений файлов в Docker/WSL
        watch: {
            usePolling: true,
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
