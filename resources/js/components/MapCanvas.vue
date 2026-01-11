<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="pixel-map"></canvas>
        <div v-if="isLoading" class="loading-overlay">
            Loading World...
        </div>
        <div class="custom-controls">
            <div class="zoom-buttons">
                <button class="button" @click="zoomIn()"><img src="../../images/zoomIn.svg" alt="zoom button" width="24" height="24"></button>
                <button class="button" @click="zoomOut()"><img src="../../images/zoomOut.svg" alt="zoom button" width="24" height="24"></button>
            </div>
            <button class="button" @click="centerMap()"><img src="../../images/compass-icon.svg" alt="pixel art compass" width="24" height="24"></button>
        </div>

        <div id="hcaptcha-container"></div>
    </div>
</template>

<script setup>
import { onMounted, ref} from 'vue'
import { useMap } from '../composables/useMap'
import { usePixels } from '../composables/usePixels'
import { initRealtimePixels } from '../composables/realtimePixels'
import { lngLatToWorldPx, worldPxToLngLat } from '../composables/useWorldConversion'
import { executeHCaptcha } from '../composables/usecaptcha.js'

const isLoading = ref(true)

const props = defineProps(
    ['selectedColor', 'walletData']
)
const emit = defineEmits(['pixelHover'])

const { map, init, on, unproject, project,  zoomIn, zoomOut, centerMap, getBounds, getZoom } = useMap('map')
const { stored, load, save, syncCooldown} = usePixels()

const Zoom = 10;
const min_zoom = 9;
const pixelSizeAtZoom = 1


const canvas = ref(null)
let canvasRender = ref(null)
let animationFrameId = null
let captchaSessionVerified = false;
const animationPulse = ref(0)

const hoverX = ref(null)
const hoverY = ref(null)


const cursorX = ref(null)
const cursorY = ref(null)

const displayX = ref(null)
const displayY = ref(null)


onMounted(async () => {
    isLoading.value = true
    const [mapInstance] = await Promise.all([
        init(),
        load(),
        syncCooldown()
    ]);
    isLoading.value = false
    setupCanvas()
    setupEvents(mapInstance)
    initRealtimePixels(drawPixels)
    drawPixels()
})
defineExpose({ zoomIn, zoomOut, centerMap })


function setupCanvas() {
    const canvasValue = canvas.value
    const container = document.getElementById('map')
    canvasValue.width = container.clientWidth
    canvasValue.height = container.clientHeight
    canvasValue.style.position = 'absolute'
    canvasValue.style.pointerEvents = 'none'
    canvasRender = canvasValue.getContext('2d')
    canvasRender.imageSmoothingEnabled = false
    canvasRender.lineWidth = 2
    canvasRender.strokeStyle = '#ffffff'
}

function setupEvents(mapInstance) {
    on('move', () => { drawAll()})
    on('zoom', () => { drawAll()})

    on('resize', resizeCanvas)
    mapInstance.getCanvas().addEventListener('click', handleClick)
    mapInstance.getCanvas().addEventListener('mousemove', handleHover)
    mapInstance.getCanvas().addEventListener('mouseout', handleMouseOut)
}

function resizeCanvas() {
    const canvasValue = canvas.value
    const container = document.getElementById('map')
    canvasValue.width = container.clientWidth
    canvasValue.height = container.clientHeight
    drawAll()
}

function getCellScreenBounds(x, y) {
    const worldX1 = x
    const worldY1 = y
    const worldX2 = x + 1
    const worldY2 = y + 1

    const topLeft = worldPxToLngLat({ x: worldX1, y: worldY1 }, Zoom)
    const bottomRight = worldPxToLngLat({ x: worldX2, y: worldY2 }, Zoom)

    const screenTL = project([topLeft.lng, topLeft.lat])
    const screenBR = project([bottomRight.lng, bottomRight.lat])

    // Use Math.round for better pixel alignment and prevent sub-pixel rendering
    const screenX1 = Math.round(screenTL.x);
    const screenY1 = Math.round(screenTL.y);
    const screenX2 = Math.round(screenBR.x);
    const screenY2 = Math.round(screenBR.y);

    const width = Math.max(1, Math.abs(screenX2 - screenX1));
    const height = Math.max(1, Math.abs(screenY2 - screenY1));

    return {
        width: width,
        height: height,
        screenX: screenX1,
        screenY: screenY1
    };
}

function drawPixels() {
    if (!canvasRender || !canvas.value) return;

    canvasRender.clearRect(0, 0, canvas.value.width, canvas.value.height);

    const currentZoom = getZoom()

    if (currentZoom < min_zoom) {
        return;
    }

    const bounds = getBounds();
    if (!bounds) return;

    const nw = lngLatToWorldPx(bounds.getNorthWest(), Zoom);
    const se = lngLatToWorldPx(bounds.getSouthEast(), Zoom);


    const minX = Math.floor(nw.x);
    const maxX = Math.ceil(se.x);
    const minY = Math.floor(nw.y);
    const maxY = Math.ceil(se.y);

    stored.forEach(pixel => {
        // FRUSTUM CULLING CHECK
        if (pixel.x >= minX && pixel.x <= maxX && pixel.y >= minY && pixel.y <= maxY) {
            const rectBounds = getCellScreenBounds(pixel.x, pixel.y);
            // Skip pixels that are too small to render clearly
            if (rectBounds.width < 0.5 || rectBounds.height < 0.5) {
                return;
            }
            canvasRender.fillStyle = pixel.color;
            // Use integer coordinates for crisp rendering
            canvasRender.fillRect(
                Math.round(rectBounds.screenX),
                Math.round(rectBounds.screenY),
                Math.round(rectBounds.width),
                Math.round(rectBounds.height)
            );
        }
    });

    drawHoverPreview(hoverX.value, hoverY.value);
}

function drawHoverPreview(x, y) {
    if(x !== null && y !== null) {
        const bounds = getCellScreenBounds(x, y)

        const scale = 1.0 + Math.sin(animationPulse.value) * 0.1;
        const scaledWidth = bounds.width * scale;
        const scaledHeight = bounds.height * scale;
        const offsetX = (bounds.width - scaledWidth) / 2;
        const offsetY = (bounds.height - scaledHeight) / 2;

        const pulseX = bounds.screenX + offsetX;
        const pulseY = bounds.screenY + offsetY;

        canvasRender.fillStyle = props.selectedColor;
        // Use integer coordinates for crisp rendering
        canvasRender.fillRect(
            Math.round(pulseX),
            Math.round(pulseY),
            Math.round(scaledWidth),
            Math.round(scaledHeight)
        )
    }
}

function drawAll() {
    drawPixels()
}

function animateHover() {
    if (cursorX.value == null) {
        animationFrameId = null
        return
    }

    const followSpeed = 0.1 // smaller = slower follow

    displayX.value += (cursorX.value - displayX.value) * followSpeed
    displayY.value += (cursorY.value - displayY.value) * followSpeed


    hoverX.value = displayX.value
    hoverY.value = displayY.value

    animationPulse.value += 0.025
    drawPixels()

    animationFrameId = requestAnimationFrame(animateHover)
}

async function handleClick(mouseEvent) {
    let token = null;

    if (!captchaSessionVerified) {
        token = await executeHCaptcha();
        if (!token) return;
    }

    const xPixel = mouseEvent.clientX;
    const yPixel = mouseEvent.clientY;
    const lngLat = unproject([xPixel, yPixel]);
    const worldPixel = lngLatToWorldPx(lngLat, Zoom);

    const x = Math.floor(worldPixel.x);
    const y = Math.floor(worldPixel.y);

    const ok = await save(x, y, props.selectedColor, token);

    if (ok) {
        captchaSessionVerified = true;
        drawAll();
    }
}

function handleHover(mouseEvent) {
    const xPixel = mouseEvent.clientX
    const yPixel = mouseEvent.clientY
    const lngLat = unproject([xPixel, yPixel])
    const worldPixel = lngLatToWorldPx(lngLat, Zoom)

    cursorX.value = Math.floor(worldPixel.x)
    cursorY.value = Math.floor(worldPixel.y)

    if (displayX.value == null) {
        displayX.value = cursorX.value
        displayY.value = cursorY.value
    }

    emit('pixelHover', `${cursorX.value}, ${cursorY.value}`)

    if (animationFrameId === null) {
        animationFrameId = requestAnimationFrame(animateHover)
    }
}

function handleMouseOut() {
    cancelAnimationFrame(animationFrameId)
    animationFrameId = null

    cursorX.value = null
    cursorY.value = null

    displayX.value = null
    displayY.value = null

    hoverX.value = null
    hoverY.value = null
    animationPulse.value = 0
}

</script>

<style scoped>
#mapContainer {
    position: relative;
    width: 100%;
    height: 100%;
    background-color: #1e1e1e;
}

#map, #pixel-map {
    image-rendering: pixelated;
    image-rendering: crisp-edges;
    image-rendering: -moz-crisp-edges;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    will-change: transform;
    transform: translateZ(0);
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 2rem;
    pointer-events: none;
}

.custom-controls {
    position: absolute;
    bottom: 50px;
    right: 10px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    pointer-events: auto;
}

.custom-controls button {
    cursor: pointer;
    background: transparent;
    border: none;
    padding: 0;
    width: 36px;
}

.button img {
    width: 100%;
}

.zoom-buttons{
    display: flex;
    flex-direction: column;
    border: 2px solid white;
    border-radius: 8px;
    width: 36px;
}

.zoom-buttons button {
    border: none;
    background: transparent;
    width: 100%;
    height: 36px;
}

.zoom-buttons button:first-child {
    border-bottom: 2px solid white;
}

.zoom-buttons button:hover {
    background: #ffffff20;
}
</style>
