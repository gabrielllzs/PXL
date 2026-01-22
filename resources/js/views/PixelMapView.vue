<template>
    <div id="overlayContainer" :class="{ 'picker-open': paintMode }">
        <SideMenu :hidden="paintMode" />
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
        <Buttons :hidden="paintMode" />
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
        @verification-required="handleVerificationRequired"
        @pixel-placed="handlePixelPlaced"
    />
</template>

<script setup>
import { ref, defineAsyncComponent, watch, computed, onMounted, onUnmounted } from 'vue'
import { ColorPicker, CooldownInfo, Auth, SideMenu, Buttons, WelcomeModal } from '@/components/ui'
import ToastContainer from '@/components/ToastContainer.vue'
import PaintButton from '@/components/ui/PaintButton.vue'
import { usePixels } from '@/composables/usePixels'
import { useAuth } from '@/composables/useAuth'
import axios from 'axios'

const MapCanvas = defineAsyncComponent(() => import('@/components/MapCanvas.vue'))

const selectedColor = ref('')
const authRef = ref(null)
const mapCanvasRef = ref(null)
const paintMode = ref(false)
const pixelCount = ref(null)
const pixelLimit = ref(null)
const regenTimer = ref(null)
const showWelcome = ref(false)

const { cooldown } = usePixels()
const { isAuthenticated, user } = useAuth()

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

onMounted(() => { if (isAuthenticated()) startPolling() })
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

function handleZoomUp() {
    mapCanvasRef.value?.zoomIn()
}

function handleToggleEraser(isEraser) {
    if (isEraser) selectedColor.value = 'transparent'
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
