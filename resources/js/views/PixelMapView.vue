<template>
    <div id="overlayContainer" :class="{ 'picker-open': paintMode }">
        <SideMenu 
            :hidden="paintMode"
            :mapCenter="mapCenter"
            :mapZoom="mapZoom"
            @navigateToLocation="handleNavigateToLocation"
        />
        <ColorPicker 
            v-model="selectedColor" 
            :paintMode="paintMode"
            :pixelCount="pixelCount"
            :disabled="isPaintDisabled"
            @close="paintMode = false"
            @zoomUp="handleZoomUp"
            @toggleEraser="handleToggleEraser"
        />
        <PaintButton 
            v-model="paintMode" 
            :pixelCount="pixelCount"
            :pixelLimit="pixelLimit"
            :regenTimer="regenTimer"
            :disabled="isPaintDisabled"
            :showHint="false"
        />
        <CooldownInfo v-if="cooldown.active && !isAuthenticated()" :seconds="cooldown.remaining" />
        <Auth ref="authRef" :hidden="paintMode" />
        <Buttons 
            :hidden="paintMode"
            :mapCenter="mapCenter"
            :mapZoom="mapZoom"
            @navigateToLocation="handleNavigateToLocation"
        />
        <WaybackControls
            v-model="waybackActive"
            :currentTime="waybackTime"
            :minTime="minTime"
            :maxTime="maxTime"
            @timeChange="handleWaybackTimeChange"
        />
        <ToastContainer />
        <WelcomeModal 
            v-model="showWelcome" 
            videoUrl="https://www.youtube.com/watch?v=YOUR_VIDEO_ID"
        />
    </div>
    <MapCanvas
        ref="mapCanvasRef"
        :selectedColor="selectedColor"
        :paintMode="paintMode"
        :waybackActive="waybackIsActive"
        :waybackPixels="waybackPixels"
        @verification-required="handleVerificationRequired"
        @pixel-placed="handlePixelPlaced"
        @custom-color-requires-auth="handleCustomColorRequiresAuth"
    />
</template>

<script setup>
import { ref, defineAsyncComponent, watch, computed, onMounted, onUnmounted, provide, watchEffect } from 'vue'
import { ColorPicker, CooldownInfo, Auth, SideMenu, Buttons, WelcomeModal } from '@/components/ui'
import ToastContainer from '@/components/ToastContainer.vue'
import PaintButton from '@/components/ui/PaintButton.vue'
import { usePixels } from '@/composables/usePixels'
import { useAuth } from '@/composables/useAuth'
import { useSavedLocations } from '@/composables/useSavedLocations'
import { useWayback } from '@/composables/useWayback'
import axios from 'axios'
import WaybackControls from '@/components/ui/WaybackSlider.vue'
const MapCanvas = defineAsyncComponent(() => import('@/components/MapCanvas.vue'))

const selectedColor = ref('')
const authRef = ref(null)
const mapCanvasRef = ref(null)
const paintMode = ref(false)
const pixelCount = ref(null)
const pixelLimit = ref(null)
const regenTimer = ref(null)
const showWelcome = ref(false)
const waybackActive = ref(false)
const waybackTime = ref(null)
const minTime = ref(null)
const maxTime = ref(new Date())
const { cooldown } = usePixels()
const { isAuthenticated, user } = useAuth()
const { loadByKey } = useSavedLocations()
const { waybackHistory, isActive: waybackIsActive, filteredPixels: waybackPixels, loadWayback, filterByDate, setActive: setWaybackActive } = useWayback()

const mapCenter = ref(null)
const mapZoom = ref(null)

// Provide openLogin function from Auth component to child components
provide('openLogin', () => {
    authRef.value?.openLogin?.()
})

const isPaintDisabled = computed(() => 
    isAuthenticated() && pixelCount.value !== null && pixelCount.value <= 0
)

let pixelStatusInterval = null
let regenCountdownInterval = null

function clearPixelStatus() {
    pixelCount.value = pixelLimit.value = regenTimer.value = null
}

function clearIntervals() {
    if (pixelStatusInterval) { clearInterval(pixelStatusInterval); pixelStatusInterval = null }
    if (regenCountdownInterval) { clearInterval(regenCountdownInterval); regenCountdownInterval = null }
}

async function fetchPixelStatus() {
    if (!isAuthenticated()) { clearPixelStatus(); return }
    
    try {
        const { data } = await axios.get('/api/pixel-status')
        pixelCount.value = data.pixels_available
        pixelLimit.value = data.pixel_limit
        regenTimer.value = data.time_until_regeneration
        
        if (data.pixels_available <= 0 && paintMode.value) paintMode.value = false
        startRegenCountdown()
    } catch (err) {
        if (err.response?.status !== 429) console.error(err)
    }
}

function startRegenCountdown() {
    if (regenCountdownInterval) { clearInterval(regenCountdownInterval); regenCountdownInterval = null }
    if (regenTimer.value <= 0) return
    
    regenCountdownInterval = setInterval(async () => {
        regenTimer.value = Math.max(0, regenTimer.value - 1)
        if (regenTimer.value <= 0) {
            clearInterval(regenCountdownInterval)
            regenCountdownInterval = null
            await fetchPixelStatus()
            // Retry if server hasn't regenerated yet
            if (pixelCount.value <= 0 && regenTimer.value <= 0) {
                setTimeout(fetchPixelStatus, 1000)
            }
        }
    }, 1000)
}

function startPolling() {
    fetchPixelStatus()
    if (!pixelStatusInterval) pixelStatusInterval = setInterval(fetchPixelStatus, 10000)
}

onMounted(async () => { 
    if (isAuthenticated()) startPolling()
    
    // Load wayback history
    await loadWayback()
    if (waybackHistory.value.length) {
        const firstEntry = waybackHistory.value[0]
        const lastEntry = waybackHistory.value[waybackHistory.value.length - 1]
        minTime.value = new Date(firstEntry.created_at)
        maxTime.value = new Date(lastEntry.created_at)
        waybackTime.value = maxTime.value
    }
    
    // Check for shared location link in query parameter
    const urlParams = new URLSearchParams(window.location.search)
    const locationKey = urlParams.get('location')
    if (locationKey) {
        const location = await loadByKey(locationKey)
        if (location) {
            // Wait for map to be ready
            setTimeout(() => {
                handleNavigateToLocation({
                    longitude: parseFloat(location.longitude),
                    latitude: parseFloat(location.latitude),
                    zoom: location.zoom ? parseFloat(location.zoom) : null
                })
                // Clean up URL by removing query parameter
                const newUrl = window.location.pathname
                window.history.replaceState({}, '', newUrl)
            }, 500)
        }
    }
    
    // Update map center/zoom periodically
    const updateMapState = () => {
        if (mapCanvasRef.value) {
            mapCenter.value = mapCanvasRef.value.getCenter?.() || null
            mapZoom.value = mapCanvasRef.value.getZoom?.() || null
        }
    }
    const mapStateInterval = setInterval(updateMapState, 1000)
    onUnmounted(() => clearInterval(mapStateInterval))
})

onUnmounted(clearIntervals)

watch(user, (newUser) => {
    if (newUser) {
        startPolling()
    } else {
        clearPixelStatus()
        clearIntervals()
    }
})

function handlePixelPlaced(data) {
    if (!data || typeof data.pixels_available !== 'number') return
    
    pixelCount.value = data.pixels_available
    if (data.pixel_limit) pixelLimit.value = data.pixel_limit
    if (data.time_until_regeneration) {
        regenTimer.value = data.time_until_regeneration
        startRegenCountdown()
    }
    if (data.pixels_available <= 0 && paintMode.value) paintMode.value = false
}

function handleVerificationRequired() {
    authRef.value?.showVerifyModal()
}

function handleCustomColorRequiresAuth() {
    authRef.value?.openLogin?.()
}

function handleZoomUp() {
    mapCanvasRef.value?.zoomIn()
}

function handleToggleEraser(isEraser) {
    if (isEraser) selectedColor.value = 'transparent'
}

function handleNavigateToLocation(location) {
    if (mapCanvasRef.value && location) {
        mapCanvasRef.value.navigateToLocation?.(location)
    }
}

function handleWaybackTimeChange(newTime) {
    waybackTime.value = newTime
    filterByDate(newTime)
}
</script>

<style scoped>
#overlayContainer {
    position: absolute;
    z-index: 1;
    width: 100%;
    height: 100%;
    pointer-events: none;
}
</style>
