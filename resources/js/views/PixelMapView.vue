<template>
    <div id="overlayContainer">
        <SideMenu />
        <ColorPicker v-model="selectedColor" />
        <PixelInfo :info="pixelInfo" />
        <CooldownInfo v-if="cooldown.active" :seconds="cooldown.remaining" />
        <Auth ref="authRef"/>
        <ToastContainer />
    </div>
    <MapCanvas 
        ref="mapCanvasRef" 
        :selectedColor="selectedColor" 
        @pixelHover="pixelInfo = $event"
        @verification-required="handleVerificationRequired"
    />
</template>

<script setup>
import { ref, defineAsyncComponent } from 'vue'
import {
    ColorPicker,
    PixelInfo,
    CooldownInfo,
    Auth,
    SideMenu,
} from '@/components/ui'

import ToastContainer from '@/components/ToastContainer.vue'
import { usePixels } from '@/composables/usePixels'

const MapCanvas = defineAsyncComponent(() => import('@/components/MapCanvas.vue'))

const selectedColor = ref('')
const pixelInfo = ref('')
const authRef = ref(null)

const { cooldown } = usePixels()

function handleVerificationRequired() {
    if (authRef.value) {
        authRef.value.showVerifyModal()
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
