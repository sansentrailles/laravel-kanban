import '../css/app.css';
import './bootstrap'; // Уже содержит настройку axios и CSRF-токена

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia'; // Импортируем Pinia
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Kanban';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Подключаем плагины
        app.use(plugin);
        app.use(ZiggyVue);
        app.use(createPinia()); // Инициализируем хранилище состояния

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});