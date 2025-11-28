import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import {stored} from "./usePixels.js";

window.Pusher = Pusher

export function initRealtimePixels(drawAll) {
    // Completely disable wss transport
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: true,
        enabledTransports: ["ws", "wss"],
    });

    window.Echo.channel("pixel").listen(".PixelPlaced", (e) => {
        console.log("✅ Pixel received:", e);

        const index = stored.findIndex(p => p.x === e.x && p.y === e.y);
        if (index >= 0) {
            stored[index].color = e.color;
        } else {
            stored.push({ x: e.x, y: e.y, color: e.color });
        }

        drawAll();
    });

    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Connected to Reverb!');
    });

}
