import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Live notifications and messages via Pusher. window.Echo stays undefined
// when no VITE_PUSHER_APP_KEY is configured, so the app works exactly as
// before (page-load-only updates) until a Pusher app is set up.
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',
        wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
        wsPort: import.meta.env.VITE_PUSHER_PORT || 80,
        wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });
}
