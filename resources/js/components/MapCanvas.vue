<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="overlayCanvas"></canvas>
        <div v-if="isLoading" class="loading-overlay">
            Loading World...
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

const props = defineProps({
    selectedColor: String
})
const emit = defineEmits(['pixelHover'])

const Zoom = 8
const cellPixelSizeAtZoom = 1

const { map, init, on, unproject, project,  zoomIn, zoomOut, centerMap  } = useMap('map')
const { stored, load, save, syncCooldown} = usePixels()


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

    const width = screenBR.x - screenTL.x
    const height = screenBR.y - screenTL.y

    return {
        screenTL: screenTL,
        width: Math.ceil(width),
        height: Math.ceil(height),
        screenX: Math.floor(screenTL.x),
        screenY: Math.floor(screenTL.y)
    }
}

function drawPixels(){
    canvasRender.clearRect(0, 0, canvas.value.width, canvas.value.height)
    stored.forEach(cell => {
        const bounds = getCellScreenBounds(cell.x, cell.y)
        canvasRender.fillStyle = cell.color
        canvasRender.fillRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height)
    })

    drawHoverPreview(hoverX.value, hoverY.value)
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
    const rect = map.value.getCanvas().getBoundingClientRect()
    const xPixel = mouseEvent.clientX - rect.left
    const yPixel = mouseEvent.clientY - rect.top
    const lngLat = unproject([xPixel, yPixel])
    const worldPixel = lngLatToWorldPx(lngLat, Zoom)

    const x = Math.floor(worldPixel.x / cellPixelSizeAtZoom)
    const y = Math.floor(worldPixel.y / cellPixelSizeAtZoom)

    const ok = await save(x, y, props.selectedColor)
    if (ok) drawAll()
}

function handleHover(mouseEvent) {
    const rect = map.value.getCanvas().getBoundingClientRect()
    const xPixel = mouseEvent.clientX - rect.left
    const yPixel = mouseEvent.clientY - rect.top
    const lngLat = unproject([xPixel, yPixel])
    const worldPixel = lngLatToWorldPx(lngLat, Zoom)

    cursorX.value = Math.floor(worldPixel.x / cellPixelSizeAtZoom)
    cursorY.value = Math.floor(worldPixel.y / cellPixelSizeAtZoom)

    emit('pixelHover', `(${cursorX.value},${cursorY.value})`)

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
#map, #overlayCanvas {
    image-rendering: pixelated;
    image-rendering: -webkit-crisp-edges;
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
</style>
