<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="overlayCanvas">
        </canvas>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useMap } from '../composables/useMap'
import { usePixels } from '../composables/usePixels'
import { initRealtimePixels } from '../composables/realtimePixels'
import { lngLatToWorldPx, worldPxToLngLat } from '../composables/useWorldConversion'

const props = defineProps({
    selectedColor: String
})
const emit = defineEmits(['pixelHover'])

const Zoom = 8
const cellPixelSizeAtZoom = 1

const { map, init, on, unproject, project,  zoomIn, zoomOut, centerMap  } = useMap('map')
const { stored, load, save, syncCooldown} = usePixels()

const canvas = ref(null)
let ctx
let animationFrameId = null
const animationPulse = ref(0)

const hoverX = ref(null)
const hoverY = ref(null)

onMounted(async () => {
    const mapInstance = init()
    await syncCooldown()
    await load()
    setupCanvas()
    setupEvents(mapInstance)
    initRealtimePixels(drawPixels)
    drawPixels()
})

defineExpose({ zoomIn, zoomOut, centerMap })


function setupCanvas() {
    const c = canvas.value
    const container = document.getElementById('map')
    c.width = container.clientWidth
    c.height = container.clientHeight
    c.style.position = 'absolute'
    c.style.pointerEvents = 'none'
    ctx = c.getContext('2d')
    ctx.lineWidth = 2
    ctx.strokeStyle = '#ffffff'
}

function setupEvents(mapInstance) {
    on('move', drawAll)
    on('zoom', drawAll)
    on('resize', resizeCanvas)
    mapInstance.getCanvas().addEventListener('click', handleClick)
    mapInstance.getCanvas().addEventListener('mousemove', handleHover)
    mapInstance.getCanvas().addEventListener('mouseout', handleMouseOut)
}

function resizeCanvas() {
    const c = canvas.value
    const container = document.getElementById('map')
    c.width = container.clientWidth
    c.height = container.clientHeight
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
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
    stored.forEach(cell => {
        const bounds = getCellScreenBounds(cell.x, cell.y)
        ctx.fillStyle = cell.color
        ctx.fillRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height)
    })

    drawHoverPreview(hoverX.value, hoverY.value)
}

function drawHoverPreview(x, y) {
    if(x !== null && y !== null) {
        const bounds = getCellScreenBounds(x, y)

        const alpha = 0.6 + Math.sin(animationPulse.value) * 0.2;

        ctx.fillStyle = props.selectedColor.slice(0, 7) + Math.round(alpha * 255).toString(16).padStart(2, '0');

        ctx.fillRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height)
    }
}

function drawAll() {
    drawPixels()
}

function animateHover(timestamp) {
    if (hoverX.value !== null) {
        animationPulse.value += 0.05;

        updateHoverState(hoverX.value, hoverY.value, true);

        animationFrameId = requestAnimationFrame(animateHover);
    }
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

    const x = Math.floor(worldPixel.x / cellPixelSizeAtZoom)
    const y = Math.floor(worldPixel.y / cellPixelSizeAtZoom)

    if (hoverX.value !== x || hoverY.value !== y){
        updateHoverState(x, y, false)
    }

    if (animationFrameId === null) {
        animationFrameId = requestAnimationFrame(animateHover);
    }

    emit('pixelHover', `(${x},${y})`)
}

function updateHoverState(newX, newY, isAnimationTick) {
    if (!isAnimationTick && hoverX.value !== null) {
        const bounds = getCellScreenBounds(hoverX.value, hoverY.value)

        ctx.clearRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height);

        const oldPixel = stored.find(cell => cell.x === hoverX.value && cell.y === hoverY.value);
        if (oldPixel) {
            ctx.fillStyle = oldPixel.color;
            ctx.fillRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height);
        }
    }

    if (!isAnimationTick) {
        hoverX.value = newX
        hoverY.value = newY
    }

    drawHoverPreview(hoverX.value, hoverY.value);

    if (animationFrameId === null) {
        animationFrameId = requestAnimationFrame(animateHover);
    }
}

function handleMouseOut() {
    if (animationFrameId !== null) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    if (hoverX.value !== null) {
        const bounds = getCellScreenBounds(hoverX.value, hoverY.value);
        ctx.clearRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height);

        const oldPixel = stored.find(cell => cell.x === hoverX.value && cell.y === hoverY.value);
        if (oldPixel) {
            ctx.fillStyle = oldPixel.color;
            ctx.fillRect(bounds.screenX, bounds.screenY, bounds.width, bounds.height);
        }
    }

    hoverX.value = null
    hoverY.value = null
    animationPulse.value = 0;
}
</script>

<style scoped>
#mapContainer {
    position: relative;
    width: 100vw;
    height: 100vh;
}
#map, #overlayCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
</style>
