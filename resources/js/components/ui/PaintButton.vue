<template>
    <div class="paint-button-container">
        <button
            class="paint-btn"
            :class="{ active: isPaintMode }"
            @click="togglePaintMode"
            :disabled="disabled"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="paint-icon">
                <path d="M240-120q-45 0-89-22t-71-58q26 0 53-20.5t27-59.5q0-50 35-85t85-35q50 0 85 35t35 85q0 66-47 113t-113 47Zm230-240L360-470l358-358q11-11 27.5-11.5T774-828l54 54q12 12 12 28t-12 28L470-360Z"></path>
            </svg>
            <span class="paint-text">Paint</span>
            <span v-if="regenTimer > 0" class="pixel-count-badge timer">{{ Math.ceil(regenTimer) }}s</span>
            <span v-else-if="pixelCount !== null" class="pixel-count-badge">{{ pixelCount }}</span>
        </button>

        <div v-if="isPaintMode && showHintState && showHint" class="paint-hint">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="hint-icon">
                <path d="M419-80q-28 0-52.5-12T325-126L107-403l19-20q20-21 48-25t52 11l74 45v-328q0-17 11.5-28.5T340-760q17 0 29 11.5t12 28.5v472l-97-60 104 133q6 7 14 11t17 4h221q33 0 56.5-23.5T720-240v-160q0-17-11.5-28.5T680-440H461v-80h219q50 0 85 35t35 85v160q0 66-47 113T640-80H419ZM167-620q-13-22-20-47.5t-7-52.5q0-83 58.5-141.5T340-920q83 0 141.5 58.5T540-720q0 27-7 52.5T513-620l-69-40q8-14 12-28.5t4-31.5q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 17 4 31.5t12 28.5l-69 40Zm335 280Z"></path>
            </svg>
            <span><b>Click</b> or hold <kbd>SPACE</kbd> to paint</span>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    pixelCount: {
        type: Number,
        default: null
    },
    regenTimer: {
        type: Number,
        default: null
    },
    disabled: {
        type: Boolean,
        default: false
    },
    showHint: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['update:modelValue'])
const isPaintMode = ref(props.modelValue)
const showHintState = ref(false)

function togglePaintMode() {
    isPaintMode.value = !isPaintMode.value
    emit('update:modelValue', isPaintMode.value)

    // Show hint briefly when activating paint mode
    if (isPaintMode.value && props.showHint) {
        showHintState.value = true
        setTimeout(() => {
            showHintState.value = false
        }, 3000)
    }
}

watch(() => props.modelValue, (newVal) => {
    isPaintMode.value = newVal
})

// Handle SPACE key to toggle paint mode
function handleKeyDown(e) {
    if (e.code === 'Space' && !e.repeat && !props.disabled) {
        // Don't prevent default if user is typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return
        }
        e.preventDefault()
        if (!isPaintMode.value) {
            togglePaintMode()
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.paint-button-container {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    pointer-events: auto;
    z-index: 2;
}

.paint-button-container:has(.paint-btn.active) {
    z-index: 1;
}

.paint-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #3b82f6;
    border: 2px solid #1d4ed8;
    border-radius: 10px;
    color: #fff;
    font-weight: 600;
    font-family: 'pixel art', monospace;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s;
    box-shadow: 0 4px 0 #1d4ed8;
    pointer-events: auto;
}

.paint-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #1d4ed8;
}

.paint-btn:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #1d4ed8;
}

.paint-btn.active {
    background: #22c55e;
    border-color: #166534;
    box-shadow: 0 4px 0 #166534;
}

.paint-btn.active:hover:not(:disabled) {
    box-shadow: 0 6px 0 #166534;
}

.paint-btn.active:active:not(:disabled) {
    box-shadow: 0 1px 0 #166534;
}

.paint-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.paint-icon {
    width: 20px;
    height: 20px;
}

.paint-text {
    font-size: 15px;
}

.pixel-count-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    min-width: 30px;
    text-align: center;
}

.pixel-count-badge.timer {
    background: rgba(245, 158, 11, 0.3);
    color: #fef3c7;
}

.paint-hint {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 12px;
    background: rgba(0, 0, 0, 0.85);
    color: #fff;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
    pointer-events: none;
    z-index: 10;
    animation: fadeInOut 3s ease-in-out;
}

.hint-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.paint-hint kbd {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    font-family: monospace;
}

@keyframes fadeInOut {
    0%, 100% { opacity: 0; transform: translateX(-50%) translateY(-4px); }
    10%, 90% { opacity: 1; transform: translateX(-50%) translateY(0); }
}

@media (max-width: 768px) {

    .paint-btn {
        padding: 10px 16px;
        font-size: 14px;
    }

    .paint-icon {
        width: 18px;
        height: 18px;
    }

    .paint-hint {
        font-size: 12px;
        padding: 6px 12px;
    }
}
</style>
