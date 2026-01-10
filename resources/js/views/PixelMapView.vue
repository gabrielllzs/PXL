<template>
    <div id="overlayContainer">
        <ColorPicker v-model="selectedColor" />
        <PixelInfo :info="pixelInfo" />
        <CooldownInfo v-if="cooldown.active" :seconds="cooldown.remaining" />
        <WalletConnect/>
        <ToastContainer />
        <PauseMenu />
    </div>
    <MapCanvas ref="mapCanvasRef" :selectedColor="selectedColor" @pixelHover="pixelInfo = $event" :walletData="currentWalletData"/>
</template>

<script setup>
import { ref, defineAsyncComponent, computed } from 'vue'
import {
    ColorPicker,
    PixelInfo,
    CooldownInfo,
    WalletConnect,
    PauseMenu,
} from '@/components/ui'

import { useWallet } from '@/composables/connectWallet' // Import useWallet
import ToastContainer from '@/components/ToastContainer.vue'
import { usePixels } from '@/composables/usePixels'

const MapCanvas = defineAsyncComponent(() => import('@/components/MapCanvas.vue'))

const { buyer, walletSignature, hasReduction } = useWallet()
const selectedColor = ref('')
const pixelInfo = ref('')

const { cooldown } = usePixels()

const currentWalletData = computed(() => {
    if (!buyer || !buyer.value) return null

    return {
        publicKey: buyer.value,
        signature: walletSignature.value?.signature,
        message: walletSignature.value?.message,
        hasReduction: hasReduction.value || false,
    }
})
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
