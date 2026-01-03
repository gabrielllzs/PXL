<template>
    <div id="overlayContainer">
        <ColorPicker v-model="selectedColor" />
        <PixelInfo :info="pixelInfo" />
        <CooldownInfo v-if="cooldown.active" :seconds="cooldown.remaining" />
        <WalletConnect/>
        <ToastContainer />
    </div>
    <MapCanvas ref="mapCanvasRef" :selectedColor="selectedColor" @pixelHover="pixelInfo = $event" />
</template>

<script setup>
import { ref, defineAsyncComponent } from 'vue'
import {
    ColorPicker,
    PixelInfo,
    CooldownInfo,
    WalletConnect,
    PauseMenu,
} from '@/components/ui'

import ToastContainer from '@/components/ToastContainer.vue'
import { usePixels } from '@/composables/usePixels'

const MapCanvas = defineAsyncComponent(() =>
    import('@/components/MapCanvas.vue')
)

const selectedColor = ref('')
const pixelInfo = ref('')

const { cooldown } = usePixels()

</script>

<style scoped>
#overlayContainer {
    position: absolute;
    z-index: 1;
    width: 100vw;
    height: 100vh;
    pointer-events: none;
}

</style>
