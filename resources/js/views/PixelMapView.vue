<template>
    <div id="overlayContainer" :class="{ 'picker-open': paintMode }">
        <SideMenu :hidden="paintMode" />
        <ColorPicker 
            v-model="selectedColor" 
            :paintMode="paintMode"
            :pixelCount="pixelCount"
            :disabled="isAuthenticated() && pixelCount !== null && pixelCount <= 0"
            @close="paintMode = false"
            @zoomUp="handleZoomUp"
            @toggleEraser="handleToggleEraser"
        />
        <PaintButton 
            v-model="paintMode" 
            :pixelCount="pixelCount"
            :pixelLimit="pixelLimit"
            :regenTimer="regenTimer"
            :disabled="isAuthenticated() && pixelCount !== null && pixelCount <= 0"
            :showHint="false"
        />
        <PixelInfo :info="pixelInfo" />
        <CooldownInfo v-if="cooldown.active && !isAuthenticated()" :seconds="cooldown.remaining" />
        <Auth ref="authRef" :hidden="paintMode" />
        <Buttons :hidden="paintMode" />
        <ToastContainer />
    </div>
    <MapCanvas
        ref="mapCanvasRef"
        :selectedColor="selectedColor"
        :paintMode="paintMode"
        @pixelHover="pixelInfo = $event"
        @verification-required="handleVerificationRequired"
        @pixel-placed="handlePixelPlaced"
    />
</template>

<script setup>
import { ref, defineAsyncComponent, watch } from 'vue'
import {
    ColorPicker,
    PixelInfo,
    CooldownInfo,
    Auth,
    SideMenu,
    Buttons,
} from '@/components/ui'

import ToastContainer from '@/components/ToastContainer.vue'
import PaintButton from '@/components/ui/PaintButton.vue'
import { usePixels } from '@/composables/usePixels'
import { useAuth } from '@/composables/useAuth'
import { onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const MapCanvas = defineAsyncComponent(() => import('@/components/MapCanvas.vue'))

const selectedColor = ref('')
const pixelInfo = ref('')
const authRef = ref(null)
const paintMode = ref(false)
const pixelCount = ref(null)
const pixelLimit = ref(null)
const regenTimer = ref(null)

const { cooldown } = usePixels()
const { isAuthenticated } = useAuth()

let pixelStatusInterval = null
let regenCountdownInterval = null

async function fetchPixelStatus() {
    if (!isAuthenticated()) {
        pixelCount.value = null
        pixelLimit.value = null
        regenTimer.value = null
        return
    }
    
    try {
        const { data } = await axios.get('/api/pixel-status')
        pixelCount.value = data.pixels_available
        pixelLimit.value = data.pixel_limit
        regenTimer.value = data.time_until_regeneration
        
        if (data.pixels_available <= 0 && paintMode.value) {
            paintMode.value = false
        }
        
        startRegenCountdown()
    } catch (err) {
        if (err.response?.status === 429) return
    }
}

function startRegenCountdown() {
    if (regenCountdownInterval) {
        clearInterval(regenCountdownInterval)
        regenCountdownInterval = null
    }
    
    if (regenTimer.value > 0) {
        regenCountdownInterval = setInterval(() => {
            if (regenTimer.value > 0) {
                regenTimer.value = Math.max(0, regenTimer.value - 1)
            }
            if (regenTimer.value <= 0) {
                clearInterval(regenCountdownInterval)
                regenCountdownInterval = null
                fetchPixelStatus()
            }
        }, 1000)
    }
}

onMounted(() => {
    if (isAuthenticated()) {
        fetchPixelStatus()
        pixelStatusInterval = setInterval(fetchPixelStatus, 10000)
    }
})

onUnmounted(() => {
    if (pixelStatusInterval) clearInterval(pixelStatusInterval)
    if (regenCountdownInterval) clearInterval(regenCountdownInterval)
})

function handlePixelPlaced(data) {
    if (data && typeof data.pixels_available === 'number') {
        pixelCount.value = data.pixels_available
        if (data.pixel_limit) {
            pixelLimit.value = data.pixel_limit
        }
        if (data.time_until_regeneration) {
            regenTimer.value = data.time_until_regeneration
            startRegenCountdown()
        }
        if (data.pixels_available <= 0 && paintMode.value) {
            paintMode.value = false
        }
    }
}

function handleVerificationRequired() {
    if (authRef.value) {
        authRef.value.showVerifyModal()
    }
}

function handleZoomUp() {
    if (mapCanvasRef.value) {
        mapCanvasRef.value.zoomIn()
    }
}

function handleToggleEraser(isEraser) {
    if (isEraser) {
        selectedColor.value = 'transparent'
    }
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
