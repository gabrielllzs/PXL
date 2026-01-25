<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    currentTime: {
        type: Date,
        default: null
    },
    minTime: {
        type: Date,
        default: null
    },
    maxTime: {
        type: Date,
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'timeChange'])

// Convert Date to timestamp for slider
const sliderValue = ref(props.currentTime ? props.currentTime.getTime() : 0)
const minTimestamp = ref(props.minTime ? props.minTime.getTime() : 0)
const maxTimestamp = ref(props.maxTime ? props.maxTime.getTime() : 0)

// Watch for prop changes
watch(() => props.currentTime, (newTime) => {
    if (newTime) {
        sliderValue.value = newTime.getTime()
    }
})

watch(() => props.minTime, (newTime) => {
    if (newTime) {
        minTimestamp.value = newTime.getTime()
    }
})

watch(() => props.maxTime, (newTime) => {
    if (newTime) {
        maxTimestamp.value = newTime.getTime()
    }
})

function handleSliderChange(event) {
    const timestamp = Number(event.target.value)
    sliderValue.value = timestamp
    emit('timeChange', new Date(timestamp))
}

function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleString()
}
</script>

<template>
    <div v-if="modelValue" class="wayback-controls">
        <div class="wayback-header">
            <h3>Wayback Machine</h3>
            <button @click="$emit('update:modelValue', false)" class="close-btn">×</button>
        </div>
        <div class="wayback-slider-container">
            <input
                type="range"
                :min="minTimestamp"
                :max="maxTimestamp"
                :value="sliderValue"
                @input="handleSliderChange"
                class="wayback-slider"
            />
        </div>
        <div class="wayback-time-display">
            <span class="time-label">Time:</span>
            <span class="time-value">{{ formatDate(currentTime) }}</span>
        </div>
    </div>
</template>

<style scoped>
.wayback-controls {
    display: flex;
    flex-direction: column;
    position: fixed;
    width: 100%;
    left: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-top: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 14px 14px 0 0;
    padding: 20px;
    z-index: 2;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    pointer-events: auto;
    box-sizing: border-box;
    max-width: 100%;
}

.wayback-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.wayback-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-btn:hover {
    background: rgba(0, 0, 0, 0.1);
}

.wayback-slider-container {
    margin: 15px 0;
}

.wayback-slider {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    outline: none;
    cursor: pointer;
}

.wayback-time-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.time-label {
    font-weight: 600;
    color: #666;
}

.time-value {
    font-weight: 500;
    color: #1e1e1e;
}
</style>
