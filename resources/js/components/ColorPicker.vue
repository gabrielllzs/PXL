<template>
    <div id="colorPicker" @click.stop>
        <div class="palette-grid">
            <div
                v-for="c in palette"
                :key="c"
                class="swatch"
                :class="{ selected: c === modelValue }"
                :style="{ background: c }"
                @click.stop="$emit('update:modelValue', c)"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '#000000'
    }
})
const emit = defineEmits(['update:modelValue'])

const palette = [
    '#000000', // black
    '#1d2b53',
    '#7e2553',
    '#008751',
    '#ab5236',
    '#5f574f',
    '#c2c3c7',
    '#fff1e8',
    '#ff004d',
    '#ffa300',
    '#ffec27',
    '#00e436',
    '#29adff',
    '#83769c',
    '#ff77a8',
    '#ffccaa'
]

const color = ref(props.modelValue)
watch(() => props.modelValue, v => (color.value = v))
</script>

<style scoped>
#colorPicker {
    position: fixed;
    bottom: 20px;
    left: 20px;
    pointer-events: auto;
    background: linear-gradient(145deg, rgba(255,255,255,0.98), rgba(255,255,255,0.92));
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 16px;
    box-shadow:
        0 20px 60px rgba(0,0,0,0.3),
        0 0 0 1px rgba(255,255,255,0.5) inset;
    width: 380px;
    max-width: calc(100% - 48px);
    border: 1px solid rgba(255,255,255,0.3);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.palette-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 8px;
}

.swatch {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
}

.swatch:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.swatch.selected {
    border: 3px solid #667eea;
    box-shadow:
        0 4px 12px rgba(102, 126, 234, 0.4),
        0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: scale(1.1);
}
</style>
