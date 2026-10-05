import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const configuredHost = import.meta.env.VITE_REVERB_HOST;
const isLocalDevHost = !configuredHost || configuredHost === 'localhost' || configuredHost === '127.0.0.1' || configuredHost.startsWith('192.168.') || configuredHost.startsWith('10.');

if (reverbKey && reverbKey !== 'local' && (!import.meta.env.PROD || !isLocalDevHost)) {
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'https';
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || (reverbScheme === 'https' ? 443 : 80));
    const wsHost = (!isLocalDevHost && configuredHost)
        ? configuredHost
        : (typeof window !== 'undefined' && window.location.hostname ? window.location.hostname : 'localhost');

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: wsHost,
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

