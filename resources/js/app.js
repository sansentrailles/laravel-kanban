import '../css/app.css';
import './bootstrap'; // Уже содержит настройку axios и CSRF-токена

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia'; // Импортируем Pinia
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    authorizer: (channel) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                })
                .then(response => callback(false, response.data))
                .catch(error => callback(true, error));
            }
        };
    },
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});

const appName = import.meta.env.VITE_APP_NAME || 'Kanban';

createInertiaApp({
    // title: (title) => `${title} - ${appName}`,
    title: (title) => title,
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