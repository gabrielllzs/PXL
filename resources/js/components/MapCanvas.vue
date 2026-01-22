<template>
    <div id="mapContainer">
        <div id="map"></div>
        <canvas ref="canvas" id="pixel-map"></canvas>
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
import { onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import ZoomControls from '@/components/ui/ZoomControls.vue'
import { useMap } from '../composables/useMap'
import { usePixels } from '../composables/usePixels'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { initRealtimePixels, groupCursors } from '../composables/realtimePixels'
import { lngLatToWorldPx, worldPxToLngLat } from '../composables/useWorldConversion'
import { executeHCaptcha } from '../composables/usecaptcha.js'
import { playPixelPlaceSound } from '../composables/useAudio.js'

// Constants
const ZOOM = 10
const MIN_ZOOM = 9.5
const CURSOR_SEND_INTERVAL = 150
const PAINT_INTERVAL = 100
const CURSOR_TIMEOUT = 3000
const MAP_POSITION_KEY = 'mapCanvas:position'

const props = defineProps({
    selectedColor: String,
    paintMode: { type: Boolean, default: false }
})

const emit = defineEmits(['pixelHover', 'verificationRequired', 'pixelPlaced'])

// Composables
const { map, init, on, unproject, project, zoomIn, zoomOut, centerMap, getBounds, getZoom, getCenter, setCenter } = useMap('map')
const { stored, load, save, syncCooldown } = usePixels()
const { user, isEmailVerified, group } = useAuth()
const { showToast } = useToast()

// Refs
const isLoading = ref(true)
const canvas = ref(null)
const hoverCanvas = ref(null)
const animationPulse = ref(0)
const cursorX = ref(null)
const cursorY = ref(null)
const displayX = ref(null)
const displayY = ref(null)
const isSpaceHeld = ref(false)
const isPainting = ref(false)

// Non-reactive state
let canvasRender = null
let hoverCanvasRender = null
let animationFrameId = null
let captchaSessionVerified = false
let lastCursorSendTime = 0
let lastPaintTime = 0
let lastPaintedX = null
let lastPaintedY = null
let urlUpdateTimeout = null
let isNavigatingFromURL = false

async function sendCursorPosition(x, y) {
    if (!group.value?.id) return
    
    const now = Date.now()
    if (now - lastCursorSendTime < CURSOR_SEND_INTERVAL) return
    lastCursorSendTime = now
    
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

onMounted(async () => {
    isLoading.value = true
    const savedPos = getSavedPosition()

    const [mapInstance] = await Promise.all([init(), load(), syncCooldown()])
    
    isLoading.value = false
    setupCanvas()
    setupEvents(mapInstance)
    initRealtimePixels(drawPixels)

    if (savedPos?.x != null && savedPos?.y != null) {
        isNavigatingFromURL = true
        mapInstance.once('load', () => {
            const lngLat = worldPxToLngLat({ x: savedPos.x, y: savedPos.y }, ZOOM)
            setCenter([lngLat.lng, lngLat.lat], savedPos.z ?? 11)
            setTimeout(() => { isNavigatingFromURL = false }, 1000)
        })
    }

    drawPixels()
})

onUnmounted(() => {
    stopPainting()
    window.removeEventListener('keydown', handleKeyDown)
    window.removeEventListener('keyup', handleKeyUp)
})

defineExpose({ zoomIn, zoomOut, centerMap })


function setupCanvas() {
    const container = document.getElementById('map')
    const { clientWidth: w, clientHeight: h } = container
    
    // Setup both canvases with shared dimensions
    ;[canvas.value, hoverCanvas.value].forEach((c, i) => {
        c.width = w
        c.height = h
        c.style.position = 'absolute'
        c.style.pointerEvents = 'none'
        if (i === 1) c.style.zIndex = '1'
    })
    
    canvasRender = canvas.value.getContext('2d')
    hoverCanvasRender = hoverCanvas.value.getContext('2d')
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
    on('move', () => { drawPixels(); savePosition() })
    on('zoom', () => { drawPixels(); savePosition() })
    on('resize', resizeCanvas)
    
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
    
    canvas.value.width = w
    canvas.value.height = h
    hoverCanvas.value.width = w
    hoverCanvas.value.height = h
    drawPixels()
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

function drawPixels() {
    if (!canvasRender || !canvas.value) return
    
    canvasRender.clearRect(0, 0, canvas.value.width, canvas.value.height)
    
    if (getZoom() < MIN_ZOOM) return
    
    const bounds = getBounds()
    if (!bounds) return
    
    // Get visible world pixel coordinates
    const nw = lngLatToWorldPx(bounds.getNorthWest(), ZOOM)
    const se = lngLatToWorldPx(bounds.getSouthEast(), ZOOM)
    const minX = Math.floor(nw.x), maxX = Math.ceil(se.x)
    const minY = Math.floor(nw.y), maxY = Math.ceil(se.y)
    
    // Draw only visible pixels
    for (const pixel of stored) {
        if (pixel.x >= minX && pixel.x <= maxX && pixel.y >= minY && pixel.y <= maxY) {
            const { screenX, screenY, width, height } = getCellScreenBounds(pixel.x, pixel.y)
            canvasRender.fillStyle = pixel.color
            canvasRender.fillRect(screenX, screenY, width, height)
        }
    }
}

function drawGroupCursors() {
    if (!hoverCanvasRender || !hoverCanvas.value) return
    
    const now = Date.now()
    
    // Clean up old cursors and draw active ones
    for (const [username, cursor] of Object.entries(groupCursors)) {
        if (now - cursor.lastSeen > CURSOR_TIMEOUT) {
            delete groupCursors[username]
            continue
        }
        
        // Skip own cursor
        if (cursor.username === user.value?.username) continue
        
        try {
            const { screenX, screenY, width, height } = getCellScreenBounds(cursor.x, cursor.y)
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
            hoverCanvasRender.font = '12px sans-serif'
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
            drawPixels()
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

</style>
