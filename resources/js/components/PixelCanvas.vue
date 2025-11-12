<template>
    <div id="map" style="height: 100vh; width: 100vw; position: relative;"></div>
    <div id="pixel-info" style="position: absolute; bottom: 10px; right: 10px; background: white; padding: 5px; border: 1px solid black; font-size: 12px;"></div>
</template>

<script setup>
import { onMounted } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import axios from 'axios'; // Ensure axios is installed

onMounted(() => {
    const map = new maplibregl.Map({
        container: 'map',
        style: {
            version: 8,
            name: 'blank',
            sources: {},
            layers: [
                { id: 'background', type: 'background', paint: { 'background-color': 'rgba(132, 176, 245)' } }
            ]
        },        center: [0, 0],
        zoom: 10,
        dragRotate: false,
        touchZoomRotate: false,
        pitchWithRotate: false
    });

    // --- configuration ---
    const Zref = 8;          // reference zoom where grid indices are computed
    const cellPxAtZref = 1; // how many screen pixels a cell is at Zref
    // ----------------------

    map.on('load', async () => {
        const canvas = document.createElement('canvas');
        canvas.id = 'overlayCanvas';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.pointerEvents = 'none';
        canvas.width = map.getContainer().clientWidth;
        canvas.height = map.getContainer().clientHeight;
        map.getContainer().appendChild(canvas);

        const ctx = canvas.getContext('2d');

        /// map.getCanvas().style.opacity = '0'; later an option to disable background


        // stored pixels: { i, j, color } indices in the Zref world-pixel grid
        const stored = [];

        // Fetch stored pixels from DB
        try {
            const response = await axios.get('/api/pixels');
            stored.push(...response.data);
        } catch (error) {
            console.error('Error fetching pixels:', error);
        }

        // Get world pixel coordinates at a specific zoom level for a given lng/lat
        function lngLatToWorldPx(lngLat, zoom) {
            const scale = Math.pow(2, zoom);
            const worldSize = 256 * scale;

            const x = (lngLat.lng + 180) / 360 * worldSize;

            const latRad = lngLat.lat * Math.PI / 180;
            const mercN = Math.log(Math.tan(Math.PI / 4 + latRad / 2));
            const y = worldSize / 2 - (mercN * worldSize / (2 * Math.PI));

            return { x, y };
        }

        // Convert world pixel coordinates at a specific zoom back to lng/lat
        function worldPxToLngLat(worldPx, zoom) {
            const scale = Math.pow(2, zoom);
            const worldSize = 256 * scale;

            const lng = (worldPx.x / worldSize) * 360 - 180;

            const mercN = (worldSize / 2 - worldPx.y) * (2 * Math.PI) / worldSize;
            const latRad = 2 * Math.atan(Math.exp(mercN)) - Math.PI / 2;
            const lat = latRad * 180 / Math.PI;

            return { lng, lat };
        }

        function drawAll() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // screen pixel size of a cell at the current zoom
            const cellScreenPx = cellPxAtZref * Math.pow(2, map.getZoom() - Zref);

            stored.forEach(cell => {
                // Get the four corners of the grid cell in world coordinates at Zref
                const worldX1 = cell.i * cellPxAtZref;
                const worldY1 = cell.j * cellPxAtZref;
                const worldX2 = (cell.i + 1) * cellPxAtZref;
                const worldY2 = (cell.j + 1) * cellPxAtZref;

                // Convert corners to lng/lat
                const topLeft = worldPxToLngLat({ x: worldX1, y: worldY1 }, Zref);
                const bottomRight = worldPxToLngLat({ x: worldX2, y: worldY2 }, Zref);

                // Project corners to current screen position
                const screenTL = map.project([topLeft.lng, topLeft.lat]);
                const screenBR = map.project([bottomRight.lng, bottomRight.lat]);

                // Calculate width and height from projected corners
                const width = screenBR.x - screenTL.x;
                const height = screenBR.y - screenTL.y;

                // draw a rectangle
                ctx.fillStyle = cell.color || 'red';
                ctx.fillRect(
                    Math.floor(screenTL.x),
                    Math.floor(screenTL.y),
                    Math.ceil(width),
                    Math.ceil(height)
                );
            });
        }

        // when user clicks: compute the world-pixel index at Zref and store it
        map.getCanvas().addEventListener('click', async (e) => {
            const rect = map.getCanvas().getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // get lng/lat at click
            const lngLat = map.unproject([x, y]);

            // convert to world pixel at Zref
            const worldPx = lngLatToWorldPx(lngLat, Zref);

            // compute integer grid indices (i, j)
            const i = Math.floor(worldPx.x / cellPxAtZref);
            const j = Math.floor(worldPx.y / cellPxAtZref);

            // avoid duplicates: replace color if same i,j exists
            const existing = stored.find(s => s.i === i && s.j === j);
            if (existing) {
                // toggle or recolor. Example: cycle through colors
                existing.color = existing.color === 'red' ? 'blue' : 'red';
            } else {
                stored.push({ i, j, color: 'red' });
            }

            // Save to DB
            try {
                await axios.post('/api/pixels', { i, j, color: existing ? existing.color : 'red' });
            } catch (error) {
                console.error('Error saving pixel:', error);
            }

            drawAll();
        });

        // on map move/zoom/resize redraw
        map.on('move', drawAll);
        map.on('zoom', drawAll);
        map.on('resize', () => {
            canvas.width = map.getContainer().clientWidth;
            canvas.height = map.getContainer().clientHeight;
            drawAll();
        });

        // initial draw
        drawAll();

        // Add mousemove listener to update pixel info
        map.getCanvas().addEventListener('mousemove', (e) => {
            const rect = map.getCanvas().getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const lngLat = map.unproject([x, y]);
            const worldPx = lngLatToWorldPx(lngLat, Zref);
            const i = Math.floor(worldPx.x / cellPxAtZref);
            const j = Math.floor(worldPx.y / cellPxAtZref);
            document.getElementById('pixel-info').textContent = `Pixel: (${i}, ${j})`;
        });
    });
});
</script>

<style scoped>
#map { height: 100vh; width: 100vw; }
</style>
