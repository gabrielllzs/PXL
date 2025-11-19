<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="overlayCanvas"></canvas>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useMap } from '../composables/useMap'
import { usePixels } from '../composables/usePixels'
import { lngLatToWorldPx, worldPxToLngLat } from '../composables/useWorldConversion'

const props = defineProps({
    selectedColor: String
})
const emit = defineEmits(['pixelHover'])

const Zref = 8
const cellPxAtZref = 1

const { map, init, on, unproject, project } = useMap('map')
const { stored, load, save } = usePixels()

const canvas = ref(null)
let ctx

onMounted(async () => {
    const mapInstance = init()
    await load()
    setupCanvas()
    setupEvents(mapInstance)
    drawAll()
})

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
        const worldX1 = cell.i * cellPxAtZref
        const worldY1 = cell.j * cellPxAtZref
        const worldX2 = (cell.i + 1) * cellPxAtZref
        const worldY2 = (cell.j + 1) * cellPxAtZref
        const topLeft = worldPxToLngLat({ x: worldX1, y: worldY1 }, Zref)
        const bottomRight = worldPxToLngLat({ x: worldX2, y: worldY2 }, Zref)
        const screenTL = project([topLeft.lng, topLeft.lat])
        const screenBR = project([bottomRight.lng, bottomRight.lat])
        const width = screenBR.x - screenTL.x
        const height = screenBR.y - screenTL.y
        ctx.fillStyle = cell.color
        ctx.fillRect(Math.floor(screenTL.x), Math.floor(screenTL.y), Math.ceil(width), Math.ceil(height))
    })
}

async function handleClick(e) {
    const rect = map.value.getCanvas().getBoundingClientRect()
    const x = e.clientX - rect.left
    const y = e.clientY - rect.top
    const lngLat = unproject([x, y])
    const worldPx = lngLatToWorldPx(lngLat, Zref)
    const i = Math.floor(worldPx.x / cellPxAtZref)
    const j = Math.floor(worldPx.y / cellPxAtZref)
    const ok = await save(i, j, props.selectedColor)
    if (ok) drawAll()
}

function handleHover(e) {
    const rect = map.value.getCanvas().getBoundingClientRect()
    const x = e.clientX - rect.left
    const y = e.clientY - rect.top
    const lngLat = unproject([x, y])
    const worldPx = lngLatToWorldPx(lngLat, Zref)
    const i = Math.floor(worldPx.x / cellPxAtZref)
    const j = Math.floor(worldPx.y / cellPxAtZref)
    emit('pixelHover', `Pixel: (${i}, ${j})`)
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
