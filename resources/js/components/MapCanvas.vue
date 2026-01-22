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
import { onMounted, ref} from 'vue'
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

const isLoading = ref(true)

const props = defineProps({
    selectedColor: String,
    paintMode: {
        type: Boolean,
        default: false
    }
})
const emit = defineEmits(['pixelHover', 'verificationRequired', 'pixelPlaced'])

const { map, init, on, unproject, project,  zoomIn, zoomOut, centerMap, getBounds, getZoom, getCenter, setCenter } = useMap('map')
const { stored, load, save, syncCooldown} = usePixels()
const { user, isEmailVerified, group } = useAuth()
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

let lastCursorSendTime = 0
const CURSOR_SEND_INTERVAL = 150

const isSpaceHeld = ref(false)
const isPainting = ref(false)
let paintInterval = null
let lastPaintTime = 0
const PAINT_INTERVAL = 100 // Minimum time between paints when holding space (ms)

async function sendCursorPosition(x, y) {
    if (!group.value?.id) {
        return
    }
    
    const now = Date.now()
    if (now - lastCursorSendTime < CURSOR_SEND_INTERVAL) return
    
    lastCursorSendTime = now
    
    try {
        await axios.post('/api/cursor/move', { x, y })
    } catch (err) {
        // Silently fail - cursor updates are not critical
    }
}

const MAP_POSITION_KEY = 'mapCanvas:position'

onMounted(async () => {
    isLoading.value = true

    // Check localStorage for saved position
    let savedX = null
    let savedY = null
    let savedZ = null
    try {
        const saved = localStorage.getItem(MAP_POSITION_KEY)
        if (saved) {
            const pos = JSON.parse(saved)
            savedX = pos.x
            savedY = pos.y
            savedZ = pos.z
        }
    } catch (e) {}

    const [mapInstance] = await Promise.all([
        init(),
        load(),
        syncCooldown()
    ]);
    isLoading.value = false
    setupCanvas()
    setupEvents(mapInstance)
    initRealtimePixels(drawPixels)

    // Navigate to saved coordinates if present
    if (savedX !== null && savedY !== null) {
        isNavigatingFromURL = true
        // Wait for map to be fully loaded
        mapInstance.once('load', () => {
            const worldPx = { x: parseFloat(savedX), y: parseFloat(savedY) }
            const lngLat = worldPxToLngLat(worldPx, Zoom)
            const zoom = savedZ ? parseFloat(savedZ) : 11
            setCenter([lngLat.lng, lngLat.lat], zoom)

            // Re-enable position updates after navigation completes
            setTimeout(() => {
                isNavigatingFromURL = false
            }, 1000)
        })
    }

    drawPixels()
})

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
    stopPainting()
    window.removeEventListener('keydown', handleKeyDown)
    window.removeEventListener('keyup', handleKeyUp)
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

        // Save position to localStorage
        try {
            localStorage.setItem(MAP_POSITION_KEY, JSON.stringify({
                x: Math.round(worldPx.x),
                y: Math.round(worldPx.y),
                z: parseFloat(zoom.toFixed(2))
            }))
        } catch (e) {}
    }, 300) // Update URL 300ms after movement stops
}

function startPainting() {
    if (isPainting.value || !props.paintMode) return
    isPainting.value = true
}

function stopPainting() {
    isPainting.value = false
    if (paintInterval) {
        clearInterval(paintInterval)
        paintInterval = null
    }
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
    
    // Add keyboard listeners for SPACE key painting
    window.addEventListener('keydown', handleKeyDown)
    window.addEventListener('keyup', handleKeyUp)
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

function drawGroupCursors() {
    if (!hoverCanvasRender || !hoverCanvas.value) return;

    const now = Date.now()
    const CURSOR_TIMEOUT = 3000 // Remove cursors after 3 seconds of inactivity

    // Clean up old cursors
    Object.keys(groupCursors).forEach(username => {
        if (now - groupCursors[username].lastSeen > CURSOR_TIMEOUT) {
            delete groupCursors[username]
        }
    })

    // Draw each group member's cursor
    Object.values(groupCursors).forEach(cursor => {
        // Skip own cursor
        if (user.value && cursor.username === user.value.username) {
            return
        }

        try {
            const bounds = getCellScreenBounds(cursor.x, cursor.y)
            const centerX = bounds.screenX + bounds.width / 2
            const centerY = bounds.screenY + bounds.height / 2

            // Draw a small circle for the cursor
            hoverCanvasRender.strokeStyle = '#3b82f6'
            hoverCanvasRender.fillStyle = 'rgba(59, 130, 246, 0.2)'
            hoverCanvasRender.lineWidth = 2
            
            hoverCanvasRender.beginPath()
            hoverCanvasRender.arc(centerX, centerY, 8, 0, Math.PI * 2)
            hoverCanvasRender.fill()
            hoverCanvasRender.stroke()

            // Draw username label
            hoverCanvasRender.fillStyle = '#3b82f6'
            hoverCanvasRender.font = '12px sans-serif'
            hoverCanvasRender.textAlign = 'center'
            hoverCanvasRender.textBaseline = 'bottom'
            hoverCanvasRender.fillText(cursor.username, centerX, centerY - 12)
        } catch (err) {
            console.error('Error drawing cursor for', cursor.username, err)
        }
    })
}

function drawHoverPreview(x, y) {
    if (!hoverCanvasRender || !hoverCanvas.value) return;

    // Clear the hover canvas
    hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height);

    // Draw group member cursors first
    drawGroupCursors()

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
        // Clear hover canvas when mouse leaves, then redraw group cursors so they stay visible
        if (hoverCanvasRender && hoverCanvas.value) {
            hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height);
            drawGroupCursors();
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

async function placePixel(mouseEvent) {
    if (user.value && !isEmailVerified()) {
        showToast('Please verify your email before placing pixels', 'error')
        emit('verificationRequired')
        return false
    }

    // Only require captcha for visitors (non-authenticated users)
    let token = null;
    const isAuthenticated = user.value !== null;

    if (!isAuthenticated && !captchaSessionVerified) {
        token = await executeHCaptcha();
        if (!token) return false;
    }

    const xPixel = mouseEvent.clientX;
    const yPixel = mouseEvent.clientY;
    const lngLat = unproject([xPixel, yPixel]);
    const worldPixel = lngLatToWorldPx(lngLat, Zoom);

    const x = Math.floor(worldPixel.x);
    const y = Math.floor(worldPixel.y);

    try {
        const result = await save(x, y, props.selectedColor, token);

        if (result && result.success) {
            captchaSessionVerified = true;
            drawAll();
            playPixelPlaceSound();
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
    if (!props.paintMode) {
        return
    }
    
    await placePixel(mouseEvent)
}

async function handlePaintWhileHolding(mouseEvent) {
    const now = Date.now()
    // Throttle painting to avoid too many requests
    if (now - lastPaintTime < PAINT_INTERVAL) {
        return
    }
    lastPaintTime = now
    
    await placePixel(mouseEvent)
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

    // Coordinates hidden per user request
    // emit('pixelHover', `${cursorX.value}, ${cursorY.value}`)
    
    // Send cursor position to group members
    sendCursorPosition(cursorX.value, cursorY.value)

    // If space is held and paint mode is active, paint while moving
    if (isSpaceHeld.value && props.paintMode && isPainting.value) {
        handlePaintWhileHolding(mouseEvent)
    }

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

    // Clear hover canvas when mouse leaves, then redraw group cursors so they stay visible
    if (hoverCanvasRender && hoverCanvas.value) {
        hoverCanvasRender.clearRect(0, 0, hoverCanvas.value.width, hoverCanvas.value.height);
        drawGroupCursors();
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

</style>
