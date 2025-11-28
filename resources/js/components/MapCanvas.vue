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

onMounted(async () => {
    const mapInstance = init()
    await syncCooldown()
    await load()
    setupCanvas()
    setupEvents(mapInstance)
    initRealtimePixels(drawAll) // Pass stored and drawAll
    drawAll()
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
}

function setupEvents(mapInstance) {
    on('move', drawAll)
    on('zoom', drawAll)
    on('resize', resizeCanvas)
    mapInstance.getCanvas().addEventListener('click', handleClick)
    mapInstance.getCanvas().addEventListener('mousemove', handleHover)
}

function resizeCanvas() {
    const c = canvas.value
    const container = document.getElementById('map')
    c.width = container.clientWidth
    c.height = container.clientHeight
    drawAll()
}

function drawAll() {
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
    stored.forEach(cell => {
        const worldX1 = cell.x * cellPixelSizeAtZoom
        const worldY1 = cell.y * cellPixelSizeAtZoom
        const worldX2 = (cell.x + 1) * cellPixelSizeAtZoom
        const worldY2 = (cell.y + 1) * cellPixelSizeAtZoom
        const topLeft = worldPxToLngLat({ x: worldX1, y: worldY1 }, Zoom)
        const bottomRight = worldPxToLngLat({ x: worldX2, y: worldY2 }, Zoom)
        const screenTL = project([topLeft.lng, topLeft.lat])
        const screenBR = project([bottomRight.lng, bottomRight.lat])
        const width = screenBR.x - screenTL.x
        const height = screenBR.y - screenTL.y
        ctx.fillStyle = cell.color
        ctx.fillRect(Math.floor(screenTL.x), Math.floor(screenTL.y), Math.ceil(width), Math.ceil(height))
    })
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



    emit('pixelHover', `(${x},${y})`)
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
