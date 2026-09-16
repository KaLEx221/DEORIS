import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

window.Pusher = Pusher;

let echoInstance = null;

/**
 * Lazy-init Laravel Echo (Reverb). Avoids WebSocket errors on every page when Reverb is off.
 */
window.initDeorisEcho = function initDeorisEcho() {
    if (echoInstance) {
        return echoInstance;
    }

    if (import.meta.env.VITE_REVERB_ENABLED === 'false') {
        return null;
    }

    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8081);
    const reverbAppKey = import.meta.env.VITE_REVERB_APP_KEY
        || document.querySelector('meta[name="deoris-reverb-app-key"]')?.content
        || '';

    if (!reverbAppKey) {
        return null;
    }

    const useTls = reverbScheme === 'https';

    echoInstance = new Echo({
        broadcaster: 'reverb',
        key: reverbAppKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: useTls,
        enabledTransports: useTls ? ['wss'] : ['ws'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    window.Echo = echoInstance;

    return echoInstance;
};
