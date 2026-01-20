import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { reactive } from 'vue'
import {stored} from "./usePixels.js";
import { useAuth } from "./useAuth.js";

export const groupCursors = reactive({})

window.Pusher = Pusher

export async function initRealtimePixels(drawAll) {
    try {
        // connect met websocket server voor realtime pixel updates
        window.Echo = new Echo({
            broadcaster: "reverb",
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: false,
            enabledTransports: ["ws"],
            disableStats: true,
        });

        // luister naar pixel updates op kanaal "pixel"
        window.Echo.channel("pixel").listen(".PixelPlaced", (e) => {
            const index = stored.findIndex(p => p.x === e.x && p.y === e.y);
            if (index >= 0) {
                stored[index].color = e.color;
            } else {
                stored.push({x: e.x, y: e.y, color: e.color});
            }

            // hertekend pixels
            drawAll();
        });

        // Subscribe to group cursor channel if user is in a group
        const { group, fetchGroup } = useAuth()
        if (!group.value) {
            await fetchGroup()
        }

        if (group.value?.id) {
            window.Echo.private(`group.${group.value.id}`)
                .listen('.GroupCursorMoved', (e) => {
                    groupCursors[e.username] = {
                        x: e.x,
                        y: e.y,
                        username: e.username,
                        lastSeen: Date.now()
                    }
                })
        }

    } catch (err) {
        console.log("Failed to init realtime pixels:", err);
    }
}
