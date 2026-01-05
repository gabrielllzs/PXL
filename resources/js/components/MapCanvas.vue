<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="pixel-map"></canvas>
        <div v-if="isLoading" class="loading-overlay">
            Loading World...
        </div>
        <div class="custom-controls">
            <button @click="zoomIn()">+</button>
            <button @click="zoomOut()">-</button>
            <button class="compass" @click="centerMap()"><img src="../../images/mccompass.png" alt="pixel art compass"></button>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref} from 'vue'
import { useMap } from '../composables/useMap'
import { usePixels } from '../composables/usePixels'
import { initRealtimePixels } from '../composables/realtimePixels'
import { lngLatToWorldPx, worldPxToLngLat } from '../composables/useWorldConversion'

const isLoading = ref(true)

const props = defineProps(
    ['selectedColor', 'walletData']
)
const emit = defineEmits(['pixelHover'])

const { map, init, on, unproject, project,  zoomIn, zoomOut, centerMap, getBounds, getZoom } = useMap('map')
const { stored, load, save, syncCooldown} = usePixels()

const Zoom = 10;
const min_zoom = 9;
const cellPixelSizeAtZoom = 1


const canvas = ref(null)
let canvasRender = ref(null)
let animationFrameId = null
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
    const worldX1 = x * cellPixelSizeAtZoom
    const worldY1 = y * cellPixelSizeAtZoom
    const worldX2 = (x + 1) * cellPixelSizeAtZoom
    const worldY2 = (y + 1) * cellPixelSizeAtZoom

    const topLeft = worldPxToLngLat({ x: worldX1, y: worldY1 }, Zoom)
    const bottomRight = worldPxToLngLat({ x: worldX2, y: worldY2 }, Zoom)

    const screenTL = project([topLeft.lng, topLeft.lat])
    const screenBR = project([bottomRight.lng, bottomRight.lat])

    return {
        width: Math.ceil(screenBR.x - screenTL.x),
        height: Math.ceil(screenBR.y - screenTL.y),
        screenX: Math.floor(screenTL.x),
        screenY: Math.floor(screenTL.y)
    }
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


    const minX = Math.floor(nw.x / cellPixelSizeAtZoom);
    const maxX = Math.ceil(se.x / cellPixelSizeAtZoom);
    const minY = Math.floor(nw.y / cellPixelSizeAtZoom);
    const maxY = Math.ceil(se.y / cellPixelSizeAtZoom);

    stored.forEach(cell => {
        // FRUSTUM CULLING CHECK
        if (cell.x >= minX && cell.x <= maxX && cell.y >= minY && cell.y <= maxY) {
            const rectBounds = getCellScreenBounds(cell.x, cell.y);
            canvasRender.fillStyle = cell.color;
            canvasRender.fillRect(rectBounds.screenX, rectBounds.screenY, rectBounds.width, rectBounds.height);
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
        canvasRender.fillRect(pulseX, pulseY, scaledWidth, scaledHeight)
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

    // optional math.round because if not rounded cause hovered pixel to be off center and will reset after next animation render
    // no round means smoother movement

    hoverX.value = displayX.value
    hoverY.value = displayY.value

    animationPulse.value += 0.025
    drawPixels()

    animationFrameId = requestAnimationFrame(animateHover)
}

async function handleClick(mouseEvent) {
    const xPixel = mouseEvent.clientX
    const yPixel = mouseEvent.clientY
    const lngLat = unproject([xPixel, yPixel])
    const worldPixel = lngLatToWorldPx(lngLat, Zoom)

    const x = Math.floor(worldPixel.x / cellPixelSizeAtZoom)
    const y = Math.floor(worldPixel.y / cellPixelSizeAtZoom)

    const ok = await save(x, y, props.selectedColor, props.walletData)
    if (ok) drawAll()
}

function handleHover(mouseEvent) {
    const xPixel = mouseEvent.clientX
    const yPixel = mouseEvent.clientY
    const lngLat = unproject([xPixel, yPixel])
    const worldPixel = lngLatToWorldPx(lngLat, Zoom)

    cursorX.value = Math.floor(worldPixel.x / cellPixelSizeAtZoom)
    cursorY.value = Math.floor(worldPixel.y / cellPixelSizeAtZoom)

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
    width: 100vw;
    height: 100vh;
    background-color: #1e1e1e; /* Dark background while loading */
}

#map, #pixel-map {
    image-rendering: pixelated;
    image-rendering: -moz-crisp-edges;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
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
    gap: 5px;
    pointer-events: auto;
}

.custom-controls button {
    padding: 8px 12px;
    font-size: 16px;
    cursor: pointer;
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.compass img{
    width: 20px;
    height: 20px;
}
</style>
