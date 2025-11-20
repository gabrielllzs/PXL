<template>
    <div id="map"></div>

    <div id="pixel-info"></div>

    <div id="overlayContainer">
        <div id="colorPicker" @click.stop>
            <div class="palette-grid">
                <div v-for="c in palette" :key="c" class="swatch" :style="{ background: c, border: c === selectedColor ? '2px solid #000' : '1px solid #ccc' }" @click.stop="selectColor(c)"></div>
                <input type="color" v-model="selectedColor" @click.stop class="color-input" />
            </div>
            <div class="selected-row">Selected: <span class="selected-box" :style="{ background: selectedColor }"></span> <span class="selected-hex">{{ selectedColor }}</span></div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import maplibregl from 'maplibre-gl';
import axios from 'axios';

const selectedColor = ref('#ff0000');
const palette = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffffff', '#000000'];

const Zref = 8;
const cellPxAtZref = 1;

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
        },
        center: [0, 0],
        zoom: 10,
        dragRotate: false,
        touchZoomRotate: false,
        pitchWithRotate: false
    });

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

        // stored pixels: { id?, i, j, color } indices in the Zref world-pixel grid
        const stored = [];

        // Fetch stored pixels from DB
        try {
            const response = await axios.get('/api/pixels');
            // expect array of { id?, i, j, color }
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
                ctx.fillStyle = cell.color || '#000000';
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

            // check if there is an existing pixel at i,j
            const existingIndex = stored.findIndex(p => p.i === i && p.j === j);
            if (existingIndex !== -1) {
                // update local
                stored[existingIndex].color = selectedColor.value;
                // update on server (if id exists use PUT, otherwise fallback to POST)
                const existing = stored[existingIndex];
                try {
                    if (existing.id) {
                        await axios.put(`/api/pixels/${existing.id}`, { color: selectedColor.value });
                    } else {
                        const res = await axios.post('/api/pixels', { i, j, color: selectedColor.value });
                        if (res.data && res.data.id) existing.id = res.data.id;
                    }
                } catch (error) {
                    console.error('Error updating pixel:', error);
                }
            } else {
                // create new
                const newCell = { i, j, color: selectedColor.value };
                stored.push(newCell);
                try {
                    const res = await axios.post('/api/pixels', newCell);
                    if (res.data && res.data.id) newCell.id = res.data.id;
                } catch (error) {
                    console.error('Error saving pixel:', error);
                }
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

function selectColor(c) {
    selectedColor.value = c;
}
</script>

<style scoped>
#map {
    height: 100vh;
    width: 100vw;
    position: relative;
}

/* pixel info box (moved from inline) */
#pixel-info {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: white;
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 12px;
    z-index: 12;
}

/* overlay container for controls */
#overlayContainer {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 10;
    pointer-events: none; /* let map receive clicks except inside color picker */
}

/* color picker (clicks inside should work) */
#colorPicker {
    pointer-events: auto;
    background: rgba(255,255,255,0.95);
    border-radius: 6px;
    padding: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    font-size: 12px;
    color: #333;
}

/* row with swatches and color input */
.palette-grid {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* swatches */
.swatch {
    width: 28px;
    height: 20px;
    border-radius: 4px;
    cursor: pointer;
    box-sizing: border-box;
}

/* native color input */
.color-input {
    width: 36px;
    height: 28px;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
}

/* selected display row */
.selected-row {
    margin-top: 6px;
    font-size: 12px;
    color: #333;
    display: flex;
    align-items: center;
}

.selected-box {
    display: inline-block;
    width: 18px;
    height: 12px;
    border: 1px solid #999;
    vertical-align: middle;
    margin-left: 6px;
    margin-right: 6px;
}

.selected-hex {
    margin-left: 6px;
}
</style>
