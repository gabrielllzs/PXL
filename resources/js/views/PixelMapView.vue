<template>
    <div id="overlayContainer">
        <ColorPicker v-model="selectedColor" />
        <AppHamburger />
        <PixelInfo :info="pixelInfo" />
        <CooldownInfo v-if="cooldown.active" :seconds="cooldown.remaining" />
        <WalletConnect :connect="connectWallet" />
        <div class="custom-controls">
            <button @click="mapCanvasRef.zoomIn()">+</button>
            <button @click="mapCanvasRef.zoomOut()">-</button>
            <button class="compass" @click="mapCanvasRef.centerMap()"><img src="../../images/mccompass.png" alt="pixel art compass"></button>
        </div>
        <ToastContainer />
    </div>

    <MapCanvas ref="mapCanvasRef" :selectedColor="selectedColor" @pixelHover="pixelInfo = $event" />
</template>

<script setup>
import { ref } from 'vue'
import AppHamburger from '@/components/AppHamburger.vue'
import ColorPicker from '@/components/ColorPicker.vue'
import PixelInfo from '@/components/PixelInfo.vue'
import CooldownInfo from '@/components/CooldownInfo.vue'
import WalletConnect from "@/components/WalletConnect.vue";
import MapCanvas from '@/components/MapCanvas.vue'
import ToastContainer from '@/components/ToastContainer.vue'
import { usePixels } from '@/composables/usePixels'
import { connectWallet } from '@/composables/connectWallet'

const selectedColor = ref('')
const pixelInfo = ref('')

const { cooldown } = usePixels()
const mapCanvasRef = ref(null)

</script>

<style scoped>
#overlayContainer {
    position: absolute;
    z-index: 1;
    width: 100vw;
    height: 100vh;
    pointer-events: none;
}


.custom-controls {
    position: absolute;
    bottom: 50px;
    right: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    z-index: 10;
    pointer-events: auto;
}

.custom-controls button {
    padding: 8px 12px;
    font-size: 16px;
    cursor: pointer;
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.compass img{
    width: 20px;
    height: 20px;
}

</style>
