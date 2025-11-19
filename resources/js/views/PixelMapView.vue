<template>
    <div id="overlayContainer">
        <ColorPicker v-model="selectedColor" />
        <PixelInfo :info="pixelInfo" />
        <CooldownInfo v-if="cooldown.active" :seconds="cooldown.remaining" />
    </div>

    <MapCanvas :selectedColor="selectedColor" @pixelHover="pixelInfo = $event" />
</template>

<script setup>
import { ref } from 'vue'
import ColorPicker from '@/components/ColorPicker.vue'
import PixelInfo from '@/components/PixelInfo.vue'
import CooldownInfo from '@/components/CooldownInfo.vue'
import MapCanvas from '@/components/MapCanvas.vue'
import { usePixels } from '@/composables/usePixels'

const selectedColor = ref('#000000')
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
