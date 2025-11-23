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
    '#000000', '#1d2b53', '#7e2553', '#008751', '#ab5236', '#5f574f', '#c2c3c7',
    '#fff1e8', '#ff004d', '#ffa300', '#ffec27', '#00e436', '#29adff', '#83769c', '#ff77a8', '#ffccaa'
]

const color = ref(props.modelValue)
watch(() => props.modelValue, v => (color.value = v))
</script>

<style scoped>
#colorPicker {
    position: absolute;
    bottom: 1px;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: auto;
    background: rgba(255, 255, 255);
    border-radius: 5px;
    padding: 5px;
    margin: 0;
    width: 260px;
}

.palette-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
}

.swatch {
    width: 100%;
    aspect-ratio: 1;
    cursor: pointer;
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
