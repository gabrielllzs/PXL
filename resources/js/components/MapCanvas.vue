<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="hoverCanvas" id="hover-preview"></canvas>
        <div v-if="isLoading" class="loading-overlay">
            Loading World...
        </div>
        <ZoomControls
            :raised="paintMode"
            @zoomIn="zoomIn"
            @zoomOut="zoomOut"
            @center="centerMap"
        />
        <div id="hcaptcha-container"></div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import axios from 'axios'
import ZoomControls from '@/components/ui/ZoomControls.vue'
import { useMap } from '../composables/useMap'
import { usePixels, refetchViewportTrigger } from '../composables/usePixels'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { initRealtimePixels, groupCursors, smoothedCursorPosition, removeSmoothedCursorPosition } from '../composables/realtimePixels'
import { lngLatToWorldPx, worldPxToLngLat, pixelsToGeoJSON } from '../composables/useWorldConversion'
import { executeHCaptcha } from '../composables/usecaptcha.js'
import { playPixelPlaceSound } from '../composables/useAudio.js'

// Constants
const ZOOM = 10
const MIN_ZOOM = 9.5
const CURSOR_SEND_INTERVAL = 250
const PAINT_INTERVAL = 100
const CURSOR_TIMEOUT = 3000
const MAP_POSITION_KEY = 'mapCanvas:position'
const VIEWPORT_MARGIN = 64

const props = defineProps({
    selectedColor: String,
    paintMode: { type: Boolean, default: false },
    waybackActive: { type: Boolean, default: false },
    waybackPixels: { type: Array, default: () => [] }
})

const emit = defineEmits(['pixelHover', 'verificationRequired', 'pixelPlaced', 'customColorRequiresAuth', 'disablePaintMode'])

// Composables
const { map, init, on, unproject, project, zoomIn, zoomOut, centerMap, getBounds, getZoom, getCenter, setCenter } = useMap('map')
const { save, syncCooldown } = usePixels()

const TILE_SIZE = 256
const PIXEL_RASTER_SOURCE_ID = 'pixels-raster'
const PIXEL_RASTER_LAYER_ID = 'pixels-raster-layer'
const { user, isEmailVerified, group } = useAuth()
const { showToast } = useToast()

// Refs
const isLoading = ref(true)
const hoverCanvas = ref(null)
const animationPulse = ref(0)
const cursorX = ref(null)
const cursorY = ref(null)
const displayX = ref(null)
const displayY = ref(null)
const isSpaceHeld = ref(false)
const isPainting = ref(false)

// Non-reactive state
let hoverCanvasRender = null
let animationFrameId = null
let captchaSessionVerified = false
let lastCursorSendTime = 0
let lastSentX = null
let lastSentY = null
let lastPaintTime = 0
let lastPaintedX = null
let lastPaintedY = null
let urlUpdateTimeout = null
let isNavigatingFromURL = false

async function sendCursorPosition(x, y) {
    if (!group.value?.id) return

    if (x === lastSentX && y === lastSentY) return

    const now = Date.now()
    if (now - lastCursorSendTime < CURSOR_SEND_INTERVAL) return
    lastCursorSendTime = now
    lastSentX = x
    lastSentY = y

    try {
        await axios.post('/api/cursor/move', { x, y })
    } catch {
        // Silently fail - cursor updates are not critical
    }
}

function getSavedPosition() {
    try {
        const saved = localStorage.getItem(MAP_POSITION_KEY)
        return saved ? JSON.parse(saved) : null
    } catch {
        return null
    }
}

function getViewportBounds() {
    const bounds = getBounds()
    if (!bounds) return null
    const nw = lngLatToWorldPx(bounds.getNorthWest(), ZOOM)
    const se = lngLatToWorldPx(bounds.getSouthEast(), ZOOM)
    return {
        minX: Math.floor(nw.x) - VIEWPORT_MARGIN,
        maxX: Math.ceil(se.x) + VIEWPORT_MARGIN,
        minY: Math.floor(nw.y) - VIEWPORT_MARGIN,
        maxY: Math.ceil(se.y) + VIEWPORT_MARGIN
    }
}

function refreshRasterTiles() {
    const m = map.value
    if (!m || props.waybackActive) return
    try {
        m.removeLayer(PIXEL_RASTER_LAYER_ID)
        m.removeSource(PIXEL_RASTER_SOURCE_ID)
    } catch (_) {}
    m.addSource(PIXEL_RASTER_SOURCE_ID, {
        type: 'raster',
        tiles: [getTileUrl()],
        tileSize: TILE_SIZE,
        minzoom: MIN_ZOOM
    })
    m.addLayer({
        id: PIXEL_RASTER_LAYER_ID,
        type: 'raster',
        source: PIXEL_RASTER_SOURCE_ID,
        minzoom: MIN_ZOOM,
        paint: { 'raster-opacity': 1 }
    })
}

onMounted(async () => {
    isLoading.value = true
    const savedPos = getSavedPosition()

    const [mapInstance] = await Promise.all([init(), syncCooldown()])

    isLoading.value = false
    setupCanvas()
    setupPixelLayer(mapInstance)
    setupEvents(mapInstance)
    initRealtimePixels(updatePixelLayer)

    if (props.waybackActive) updatePixelLayer()

    if (savedPos?.x != null && savedPos?.y != null) {
        isNavigatingFromURL = true
        mapInstance.once('load', () => {
            const lngLat = worldPxToLngLat({ x: savedPos.x, y: savedPos.y }, ZOOM)
            setCenter([lngLat.lng, lngLat.lat], savedPos.z ?? 11)
            setTimeout(() => { isNavigatingFromURL = false }, 1000)
        })
    }
})

// Update pixel layer when wayback data or mode changes
watch(() => props.waybackPixels, () => {
    if (props.waybackActive) updatePixelLayer()
}, { deep: true })

watch(() => props.waybackActive, (active) => {
    if (active && props.paintMode) emit('disablePaintMode')
    setPixelLayerVisibility(active)
    if (active) updatePixelLayer()
})

watch(refetchViewportTrigger, () => {
    if (props.waybackActive) updatePixelLayer()
    else refreshRasterTiles()
})

onUnmounted(() => {
    stopPainting()
    window.removeEventListener('keydown', handleKeyDown)
    window.removeEventListener('keyup', handleKeyUp)
})

function navigateToLocation(location) {
    if (!map.value || !location) return
    setCenter([location.longitude, location.latitude], location.zoom || 11)
}

defineExpose({ zoomIn, zoomOut, centerMap, getCenter, getZoom, navigateToLocation})


function setupCanvas() {
    const container = document.getElementById('map')
    const { clientWidth: w, clientHeight: h } = container

    const c = hoverCanvas.value
    c.width = w
    c.height = h
    c.style.position = 'absolute'
    c.style.pointerEvents = 'none'
    c.style.zIndex = '1'

    hoverCanvasRender = c.getContext('2d')
}

const PIXEL_SOURCE_ID = 'pixels'
const PIXEL_LAYER_ID = 'pixels-layer'

function getTileUrl() {
    const base = typeof window !== 'undefined' ? window.location.origin : ''
    return `${base}/api/tiles/{z}/{x}/{y}.png`
}

function setupPixelLayer(mapInstance) {
    if (!mapInstance.getSource(PIXEL_RASTER_SOURCE_ID)) {
        mapInstance.addSource(PIXEL_RASTER_SOURCE_ID, {
            type: 'raster',
            tiles: [getTileUrl()],
            tileSize: TILE_SIZE,
            minzoom: MIN_ZOOM
        })
    }
    if (!mapInstance.getLayer(PIXEL_RASTER_LAYER_ID)) {
        mapInstance.addLayer({
            id: PIXEL_RASTER_LAYER_ID,
            type: 'raster',
            source: PIXEL_RASTER_SOURCE_ID,
            minzoom: MIN_ZOOM,
            paint: { 'raster-opacity': 1 }
        })
    }
    if (!mapInstance.getSource(PIXEL_SOURCE_ID)) {
        mapInstance.addSource(PIXEL_SOURCE_ID, {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: [] }
        })
    }
    if (!mapInstance.getLayer(PIXEL_LAYER_ID)) {
        mapInstance.addLayer({
            id: PIXEL_LAYER_ID,
            type: 'fill',
            source: PIXEL_SOURCE_ID,
            minzoom: MIN_ZOOM,
            paint: {
                'fill-color': ['get', 'color'],
                'fill-opacity': 1
            }
        })
    }
    setPixelLayerVisibility(props.waybackActive)
}

function setPixelLayerVisibility(wayback) {
    const m = map.value
    if (!m) return
    const rasterVis = wayback ? 'none' : 'visible'
    const geojsonVis = wayback ? 'visible' : 'none'
    try {
        m.setLayoutProperty(PIXEL_RASTER_LAYER_ID, 'visibility', rasterVis)
        m.setLayoutProperty(PIXEL_LAYER_ID, 'visibility', geojsonVis)
    } catch (_) {}
}

function updatePixelLayer() {
    if (!props.waybackActive) return
    const m = map.value
    if (!m) return
    const source = m.getSource(PIXEL_SOURCE_ID)
    if (!source) return

    const b = getViewportBounds()
    if (!b) return

    const arr = Array.isArray(props.waybackPixels) ? props.waybackPixels : []
    const pixels = arr.filter((p) => p.x >= b.minX && p.x <= b.maxX && p.y >= b.minY && p.y <= b.maxY)
    const geojson = pixelsToGeoJSON(pixels, ZOOM)
    source.setData(geojson)
}

function savePosition() {
    if (isNavigatingFromURL || !map.value) return

    clearTimeout(urlUpdateTimeout)
    urlUpdateTimeout = setTimeout(() => {
        const center = getCenter()
        if (!center) return

        const worldPx = lngLatToWorldPx({ lng: center.lng, lat: center.lat }, ZOOM)
        try {
            localStorage.setItem(MAP_POSITION_KEY, JSON.stringify({
                x: Math.round(worldPx.x),
                y: Math.round(worldPx.y),
                z: parseFloat(getZoom().toFixed(2))
            }))
        } catch {}
    }, 300)
}

function startPainting() {
    if (isPainting.value || !props.paintMode) return
    isPainting.value = true
}

function stopPainting() {
    isPainting.value = false
    lastPaintedX = null
    lastPaintedY = null
}

function handleKeyDown(e) {
    // Handle SPACE key for continuous painting
    if (e.code === 'Space' && props.paintMode) {
        // Don't prevent default if user is typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return
        }
        e.preventDefault()
        if (!e.repeat) {
            isSpaceHeld.value = true
            startPainting()
        }
    }
}

function handleKeyUp(e) {
    if (e.code === 'Space') {
        isSpaceHeld.value = false
        stopPainting()
    }
}

function setupEvents(mapInstance) {
    on('move', savePosition)
    on('zoom', savePosition)
    on('moveend', () => {
        if (props.waybackActive) updatePixelLayer()
    })
    on('zoomend', () => {
        if (props.waybackActive) updatePixelLayer()
    })
    on('resize', () => {
        resizeCanvas()
        if (props.waybackActive) updatePixelLayer()
    })

    const mapCanvas = mapInstance.getCanvas()
    mapCanvas.addEventListener('click', handleClick)
    mapCanvas.addEventListener('mousemove', handleHover)
    mapCanvas.addEventListener('mouseout', handleMouseOut)

    window.addEventListener('keydown', handleKeyDown)
    window.addEventListener('keyup', handleKeyUp)
}

function resizeCanvas() {
    const container = document.getElementById('map')
    const { clientWidth: w, clientHeight: h } = container

    hoverCanvas.value.width = w
    hoverCanvas.value.height = h
}

function getCellScreenBounds(x, y) {
    const worldX1 = x
    const worldY1 = y
    const worldX2 = x + 1
    const worldY2 = y + 1

    const topLeft = worldPxToLngLat({ x: worldX1, y: worldY1 }, ZOOM)
    const bottomRight = worldPxToLngLat({ x: worldX2, y: worldY2 }, ZOOM)

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

function drawGroupCursors() {
    if (!hoverCanvasRender || !hoverCanvas.value) return

    const now = Date.now()

    // Clean up old cursors and draw active ones
    for (const [username, cursor] of Object.entries(groupCursors)) {
        if (now - cursor.lastSeen > CURSOR_TIMEOUT) {
            delete groupCursors[username]
            removeSmoothedCursorPosition(username)
            continue
        }

        // Skip own cursor
        if (cursor.username === user.value?.username) continue

        try {
            const position = smoothedCursorPosition(username, cursor.x, cursor.y)
            const { screenX, screenY, width, height } = getCellScreenBounds(position.x, position.y)
            const cx = screenX + width / 2
            const cy = screenY + height / 2

            // Draw cursor circle
            hoverCanvasRender.strokeStyle = '#3b82f6'
            hoverCanvasRender.fillStyle = 'rgba(59, 130, 246, 0.2)'
            hoverCanvasRender.lineWidth = 2
            hoverCanvasRender.beginPath()
            hoverCanvasRender.arc(cx, cy, 8, 0, Math.PI * 2)
            hoverCanvasRender.fill()
            hoverCanvasRender.stroke()

            // Draw username
            hoverCanvasRender.fillStyle = '#3b82f6'
            hoverCanvasRender.font = '12px "pixel art"'
            hoverCanvasRender.textAlign = 'center'
            hoverCanvasRender.textBaseline = 'bottom'
            hoverCanvasRender.fillText(cursor.username, cx, cy - 12)
        } catch {}
    }
}

function clearHoverCanvas() {
    if (hoverCanvasRender && hoverCanvas.value) {
        hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height)
    }
}

function drawHoverPreview(x, y) {
    if (!hoverCanvasRender || !hoverCanvas.value) return

    clearHoverCanvas()
    drawGroupCursors()

    if (x != null && y != null) {
        const { screenX, screenY, width, height } = getCellScreenBounds(x, y)
        const scale = 1.0 + Math.sin(animationPulse.value) * 0.1
        const sw = width * scale, sh = height * scale

        hoverCanvasRender.fillStyle = props.selectedColor
        hoverCanvasRender.fillRect(
            Math.round(screenX + (width - sw) / 2),
            Math.round(screenY + (height - sh) / 2),
            Math.max(1, Math.round(sw)),
            Math.max(1, Math.round(sh))
        )
    }
}

function animateHover() {
    if (cursorX.value == null) {
        animationFrameId = null
        clearHoverCanvas()
        drawGroupCursors()
        return
    }

    const followSpeed = 0.1
    displayX.value += (cursorX.value - displayX.value) * followSpeed
    displayY.value += (cursorY.value - displayY.value) * followSpeed

    animationPulse.value += 0.025
    drawHoverPreview(displayX.value, displayY.value)

    animationFrameId = requestAnimationFrame(animateHover)
}

async function placePixel(mouseEvent) {
    if (props.waybackActive) {
        return false
    }

    if (user.value && !isEmailVerified()) {
        showToast('Please verify your email before placing pixels', 'error')
        emit('verificationRequired')
        return false
    }

    // Only require captcha for visitors (non-authenticated users)
    let token = null
    if (!user.value && !captchaSessionVerified) {
        token = await executeHCaptcha()
        if (!token) return false
    }

    const lngLat = unproject([mouseEvent.clientX, mouseEvent.clientY])
    const worldPixel = lngLatToWorldPx(lngLat, ZOOM)
    const x = Math.floor(worldPixel.x)
    const y = Math.floor(worldPixel.y)

    try {
        const result = await save(x, y, props.selectedColor, token)

        if (result?.success) {
            captchaSessionVerified = true
            updatePixelLayer()
            playPixelPlaceSound()
            emit('pixelPlaced', {
                pixels_available: result.pixels_available,
                pixel_limit: result.pixel_limit,
                level: result.level,
            })
            return true
        }
        return false
    } catch (err) {
        if (err.type === 'email_not_verified') {
            showToast(err.message || 'Please verify your email', 'error')
            emit('verificationRequired')
        } else if (err.type === 'no_pixels_available') {
            stopPainting()
        } else if (err.type === 'custom_color_requires_auth') {
            emit('customColorRequiresAuth')
        }
        return false
    }
}

async function handleClick(mouseEvent) {
    if (props.paintMode) await placePixel(mouseEvent)
}

async function handlePaintWhileHolding(mouseEvent) {
    // Skip if still on the same pixel
    if (cursorX.value === lastPaintedX && cursorY.value === lastPaintedY) return

    const now = Date.now()
    if (now - lastPaintTime < PAINT_INTERVAL) return
    lastPaintTime = now
    lastPaintedX = cursorX.value
    lastPaintedY = cursorY.value
    await placePixel(mouseEvent)
}

function handleHover(mouseEvent) {
    const lngLat = unproject([mouseEvent.clientX, mouseEvent.clientY])
    const worldPixel = lngLatToWorldPx(lngLat, ZOOM)

    cursorX.value = Math.floor(worldPixel.x)
    cursorY.value = Math.floor(worldPixel.y)

    if (displayX.value == null) {
        displayX.value = cursorX.value
        displayY.value = cursorY.value
    }

    sendCursorPosition(cursorX.value, cursorY.value)

    // Paint while holding space
    if (isSpaceHeld.value && props.paintMode && isPainting.value) {
        handlePaintWhileHolding(mouseEvent)
    }

    if (animationFrameId === null) {
        animationFrameId = requestAnimationFrame(animateHover)
    }
}

function resetCursorState() {
    cancelAnimationFrame(animationFrameId)
    animationFrameId = null
    cursorX.value = cursorY.value = displayX.value = displayY.value = null
    animationPulse.value = 0
}

function handleMouseOut() {
    resetCursorState()
    clearHoverCanvas()
    drawGroupCursors()
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

#hover-preview {
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

</style>
