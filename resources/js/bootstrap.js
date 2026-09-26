import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
if (reverbKey) {
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'https';
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || (reverbScheme === 'https' ? 443 : 80));

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
        });
    } catch (e) {
        console.warn('Echo/Reverb tidak dapat diinisialisasi:', e);
    }
}

