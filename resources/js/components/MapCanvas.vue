<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="pixel-map"></canvas>
        <canvas ref="hoverCanvas" id="hover-preview"></canvas>
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
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { initRealtimePixels } from '../composables/realtimePixels'
import { lngLatToWorldPx, worldPxToLngLat } from '../composables/useWorldConversion'
import { executeHCaptcha } from '../composables/usecaptcha.js'
import { playPixelPlaceSound } from '../composables/useAudio.js'

const isLoading = ref(true)

const props = defineProps(
    ['selectedColor']
)
const emit = defineEmits(['pixelHover', 'verificationRequired'])

const { map, init, on, unproject, project,  zoomIn, zoomOut, centerMap, getBounds, getZoom, getCenter, setCenter } = useMap('map')
const { stored, load, save, syncCooldown} = usePixels()
const { user, isEmailVerified } = useAuth()
const { showToast } = useToast()

const Zoom = 10;
const min_zoom = 9.5;
const pixelSizeAtZoom = 1


const canvas = ref(null)
let canvasRender = ref(null)
const hoverCanvas = ref(null)
let hoverCanvasRender = ref(null)
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

    // Check URL for coordinates
    const urlParams = new URLSearchParams(window.location.search)
    const urlX = urlParams.get('x')
    const urlY = urlParams.get('y')
    const urlZ = urlParams.get('z')

    const [mapInstance] = await Promise.all([
        init(),
        load(),
        syncCooldown()
    ]);
    isLoading.value = false
    setupCanvas()
    setupEvents(mapInstance)
    initRealtimePixels(drawPixels)

    // Navigate to URL coordinates if present
    if (urlX !== null && urlY !== null) {
        isNavigatingFromURL = true
        // Wait for map to be fully loaded
        mapInstance.once('load', () => {
            const worldPx = { x: parseFloat(urlX), y: parseFloat(urlY) }
            const lngLat = worldPxToLngLat(worldPx, Zoom)
            const zoom = urlZ ? parseFloat(urlZ) : 11
            setCenter([lngLat.lng, lngLat.lat], zoom)

            // Re-enable URL updates after navigation completes
            setTimeout(() => {
                isNavigatingFromURL = false
            }, 1000)
        })
    }

    drawPixels()
})
defineExpose({ zoomIn, zoomOut, centerMap })


function setupCanvas() {
    const canvasValue = canvas.value
    const hoverCanvasValue = hoverCanvas.value
    const container = document.getElementById('map')
    // Setup main pixel canvas
    canvasValue.width = container.clientWidth
    canvasValue.height = container.clientHeight
    canvasValue.style.position = 'absolute'
    canvasValue.style.pointerEvents = 'none'
    canvasRender = canvasValue.getContext('2d')

    // Setup hover preview canvas (on top of main canvas)
    hoverCanvasValue.width = container.clientWidth
    hoverCanvasValue.height = container.clientHeight
    hoverCanvasValue.style.position = 'absolute'
    hoverCanvasValue.style.pointerEvents = 'none'
    hoverCanvasValue.style.zIndex = '1'
    hoverCanvasRender = hoverCanvasValue.getContext('2d')
}

let urlUpdateTimeout = null
let isNavigatingFromURL = false

function updateURL() {
    // Don't update URL if we're currently navigating from URL
    if (isNavigatingFromURL) return

    // Debounce URL updates to avoid too many history entries
    if (urlUpdateTimeout) {
        clearTimeout(urlUpdateTimeout)
    }

    urlUpdateTimeout = setTimeout(() => {
        if (!map.value) return

        const center = getCenter()
        if (!center) return

        const zoom = getZoom()
        const worldPx = lngLatToWorldPx({ lng: center.lng, lat: center.lat }, Zoom)

        const params = new URLSearchParams()
        params.set('x', Math.round(worldPx.x).toString())
        params.set('y', Math.round(worldPx.y).toString())
        params.set('z', zoom.toFixed(2))

        const newURL = `${window.location.pathname}?${params.toString()}`
        window.history.replaceState({}, '', newURL)
    }, 300) // Update URL 300ms after movement stops
}

function setupEvents(mapInstance) {
    on('move', () => {
        drawAll()
        updateURL()
    })
    on('zoom', () => {
        drawAll()
        updateURL()
    })

    on('resize', resizeCanvas)
    mapInstance.getCanvas().addEventListener('click', handleClick)
    mapInstance.getCanvas().addEventListener('mousemove', handleHover)
    mapInstance.getCanvas().addEventListener('mouseout', handleMouseOut)
}

function resizeCanvas() {
    const canvasValue = canvas.value
    const hoverCanvasValue = hoverCanvas.value
    const container = document.getElementById('map')
    canvasValue.width = container.clientWidth
    canvasValue.height = container.clientHeight
    hoverCanvasValue.width = container.clientWidth
    hoverCanvasValue.height = container.clientHeight
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

    // Bepaal de zichtbare wereld coördinaten
    const bounds = getBounds();
    if (!bounds) return;

    // Converteer de zichtbare wereld coördinaten naar wereld pixel coördinaten
    const nw = lngLatToWorldPx(bounds.getNorthWest(), Zoom);
    const se = lngLatToWorldPx(bounds.getSouthEast(), Zoom);


    // Bepaal de zichtbare pixel coördinaten
    const minX = Math.floor(nw.x);
    const maxX = Math.ceil(se.x);
    const minY = Math.floor(nw.y);
    const maxY = Math.ceil(se.y);

    // Voor elke pixel in stored, teken deze als deze binnen de zichtbare bounds valt
    stored.forEach(pixel => {
        // alleen tekenen als binnen zichtbare gebied
        if (pixel.x >= minX && pixel.x <= maxX && pixel.y >= minY && pixel.y <= maxY) {
            const rectBounds = getCellScreenBounds(pixel.x, pixel.y);
            canvasRender.fillStyle = pixel.color;
            // Use integer coordinates for crisp rendering
            const x = rectBounds.screenX;
            const y = rectBounds.screenY;
            const w = rectBounds.width;
            const h = rectBounds.height;

            canvasRender.fillRect(x, y, w, h);
        }
    });

    // Note: hover preview is now drawn on separate canvas, not here
}

function drawHoverPreview(x, y) {
    if (!hoverCanvasRender || !hoverCanvas.value) return;

    // Clear the hover canvas
    hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height);

    if(x !== null && y !== null) {
        const bounds = getCellScreenBounds(x, y)

        const scale = 1.0 + Math.sin(animationPulse.value) * 0.1;
        const scaledWidth = bounds.width * scale;
        const scaledHeight = bounds.height * scale;
        const offsetX = (bounds.width - scaledWidth) / 2;
        const offsetY = (bounds.height - scaledHeight) / 2;

        const pulseX = bounds.screenX + offsetX;
        const pulseY = bounds.screenY + offsetY;

        hoverCanvasRender.fillStyle = props.selectedColor;

        const hx = Math.round(pulseX);
        const hy = Math.round(pulseY);
        const hw = Math.max(1, Math.round(scaledWidth));
        const hh = Math.max(1, Math.round(scaledHeight));

        hoverCanvasRender.fillRect(hx, hy, hw, hh);
    }
}

function drawAll() {
    drawPixels()
}

function animateHover() {
    if (cursorX.value == null) {
        animationFrameId = null
        // Clear hover canvas when mouse leaves
        if (hoverCanvasRender && hoverCanvas.value) {
            hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height);
        }
        return
    }

    const followSpeed = 0.1 // smaller = slower follow

    displayX.value += (cursorX.value - displayX.value) * followSpeed
    displayY.value += (cursorY.value - displayY.value) * followSpeed


    hoverX.value = displayX.value
    hoverY.value = displayY.value

    animationPulse.value += 0.025
    // Only redraw the hover preview, not all pixels!
    drawHoverPreview(hoverX.value, hoverY.value)

    animationFrameId = requestAnimationFrame(animateHover)
}

async function handleClick(mouseEvent) {
    if (user.value && !isEmailVerified()) {
        showToast('Please verify your email before placing pixels', 'error')
        emit('verificationRequired')
        return
    }

    // Only require captcha for visitors (non-authenticated users)
    let token = null;
    const isAuthenticated = user.value !== null;

    if (!isAuthenticated && !captchaSessionVerified) {
        token = await executeHCaptcha();
        if (!token) return;
    }

    const xPixel = mouseEvent.clientX;
    const yPixel = mouseEvent.clientY;
    const lngLat = unproject([xPixel, yPixel]);
    const worldPixel = lngLatToWorldPx(lngLat, Zoom);

    const x = Math.floor(worldPixel.x);
    const y = Math.floor(worldPixel.y);

    try {
        const ok = await save(x, y, props.selectedColor, token);

        if (ok) {
            captchaSessionVerified = true;
            drawAll();
            playPixelPlaceSound();
        }
    } catch (err) {
        if (err.type === 'email_not_verified') {
            showToast(err.message || 'Please verify your email', 'error')
            emit('verificationRequired')
        }
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

    // Clear hover canvas when mouse leaves
    if (hoverCanvasRender && hoverCanvas.value) {
        hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height);
    }
}

</script>

<style scoped>
#mapContainer {
    position: relative;
    width: 100%;
    height: 100%;
    background-color: #1e1e1e;
}

#map {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

#pixel-map, #hover-preview {
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
    z-index: 1;
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
    gap: 8px;
    pointer-events: auto;
}

.custom-controls button {
    cursor: pointer;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    padding: 8px;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.custom-controls button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.1);
    background: rgba(255, 255, 255, 1);
}

.custom-controls button:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.button img {
    width: 24px;
    height: 24px;
    display: block;
}

.zoom-buttons {
    display: flex;
    flex-direction: column;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    width: 44px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.zoom-buttons button {
    border: none;
    background: transparent;
    width: 100%;
    height: 36px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.zoom-buttons button:first-child {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.zoom-buttons button:hover {
    background: rgba(0, 0, 0, 0.05);
}
</style>
