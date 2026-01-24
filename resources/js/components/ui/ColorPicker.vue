<script setup>
import { ref, watch, inject } from 'vue'
import { useAuth } from '@/composables/useAuth'

const STORAGE_KEY = 'colorPicker:selectedColor'
const CUSTOM_COLORS_KEY = 'colorPicker:customColors'

const openLogin = inject('openLogin', null)
const { isAuthenticated } = useAuth()

const props = defineProps({
    modelValue: {
        type: String,
        default: '#000000'
    },
    paintMode: {
        type: Boolean,
        default: false
    },
    pixelCount: {
        type: Number,
        default: null
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'close', 'zoomUp', 'toggleEraser'])

const palette = [
    '#000000', '#ffffff', '#808080', '#c0c0c0',
    '#ff0000', '#ff6600', '#ffcc00', '#ffff00',
    '#00ff00', '#00ccff', '#0066ff', '#0000ff',
    '#6600ff', '#ff00ff', '#ff0066', '#ff3399',
    '#8b4513', '#654321', '#228b22', '#008080',
    '#ffa500', '#ff1493', '#00ffff', '#800080'
]


const customColors = ref([])
try {
    const stored = localStorage.getItem(CUSTOM_COLORS_KEY)
    if (stored) {
        const parsed = JSON.parse(stored)
        if (Array.isArray(parsed) && parsed.length <= 8) {
            customColors.value = parsed
        }
    }
} catch (e) {}

const showColorInput = ref(false)
const newColor = ref('#000000')
const colorInputRef = ref(null)
const isEraser = ref(false)

function openColorInput() {
    if (!isAuthenticated()) {
        openLogin?.()
        return
    }
    showColorInput.value = true
    if (colorInputRef.value) {
        setTimeout(() => colorInputRef.value?.click(), 100)
    }
}

function close() {
    emit('close')
}

function zoomUp() {
    emit('zoomUp')
}

function toggleEraser() {
    isEraser.value = !isEraser.value
    emit('toggleEraser', isEraser.value)
    if (isEraser.value) {
        emit('update:modelValue', 'transparent')
    }
}

function selectColor(value) {
    if (isEraser.value) {
        isEraser.value = false
        emit('toggleEraser', false)
    }
    const isCustomColor = !palette.includes(value)
    if (!isAuthenticated() && isCustomColor) {
        openLogin?.()
        return
    }
    showColorInput.value = false
    emit('update:modelValue', value)
}

function saveCustomColors() {
    try {
        localStorage.setItem(CUSTOM_COLORS_KEY, JSON.stringify(customColors.value))
    } catch (e) {}
}

function addCustomColor() {
    const color = newColor.value.toUpperCase()
    if (customColors.value.includes(color) || customColors.value.length >= 8) return
    if (palette.includes(color)) return

    customColors.value.push(color)
    saveCustomColors()
    selectColor(color)
    showColorInput.value = false
    newColor.value = '#000000'
}

function deleteCustomColor(index) {
    const deletedColor = customColors.value[index]
    customColors.value.splice(index, 1)
    saveCustomColors()
    if (deletedColor === props.modelValue) {
        selectColor('#000000')
    }
}

let selectedColor = '#000000'
try {
    const storedColor = localStorage.getItem(STORAGE_KEY)
    if (storedColor) selectedColor = storedColor
} catch {}

if (selectedColor !== props.modelValue) {
    emit('update:modelValue', selectedColor)
}

watch(
    () => props.modelValue,
    v => {
        try {
            if (v) localStorage.setItem(STORAGE_KEY, v)
        } catch (e) {}
    }
)
</script>

<template>
    <Transition name="slide-up">
        <div v-if="paintMode" id="colorPicker" @click.stop>
            <!-- Hint at top -->
            <div class="paint-hint-banner">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="hint-icon">
                    <path d="M419-80q-28 0-52.5-12T325-126L107-403l19-20q20-21 48-25t52 11l74 45v-328q0-17 11.5-28.5T340-760q17 0 29 11.5t12 28.5v472l-97-60 104 133q6 7 14 11t17 4h221q33 0 56.5-23.5T720-240v-160q0-17-11.5-28.5T680-440H461v-80h219q50 0 85 35t35 85v160q0 66-47 113T640-80H419ZM167-620q-13-22-20-47.5t-7-52.5q0-83 58.5-141.5T340-920q83 0 141.5 58.5T540-720q0 27-7 52.5T513-620l-69-40q8-14 12-28.5t4-31.5q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 17 4 31.5t12 28.5l-69 40Zm335 280Z"></path>
                </svg>
                <span><b>Click</b> <span class="touchscreen-hidden">or hold <kbd>SPACE</kbd></span> to paint.</span>
            </div>

            <!-- Header with controls -->
            <div class="picker-header">
                <div class="header-title">
                    <h2>Paint pixel <span class="color-preview" :style="{ background: modelValue }"></span></h2>
                    <button class="header-btn color-picker-btn" @click="openColorInput" title="Color Picker">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="icon">
                            <path d="M120-120v-190l358-358-58-56 58-56 76 76 124-124q5-5 12.5-8t15.5-3q8 0 15 3t13 8l94 94q5 6 8 13t3 15q0 8-3 15.5t-8 12.5L705-555l76 78-57 57-56-58-358 358H120Zm80-80h78l332-334-76-76-334 332v78Zm447-410 96-96-37-37-96 96 37 37Zm0 0-37-37 37 37Z"></path>
                        </svg>
                    </button>
                </div>

                <button class="header-btn close-btn" @click="close" title="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="icon">
                        <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"></path>
                    </svg>
                </button>
            </div>

            <!-- Color Grid -->
            <div class="color-grid-container">
                <div class="palette-wrapper">
                    <div class="main-palette-section">
                        <div class="palette-label">Color Palette</div>
                        <div class="palette-grid main-palette">
                            <div
                                v-for="color in palette"
                                :key="color"
                                class="swatch"
                                :class="{ selected: color === modelValue }"
                                :style="{ background: color }"
                                @click.stop="selectColor(color)"
                            ></div>
                        </div>
                    </div>

                    <!-- Custom Palette (always visible) -->
                    <div class="custom-palette-section">
                        <div class="palette-label">Custom Colors</div>
                        <div class="palette-grid custom-grid">
                            <div
                                v-for="(color, index) in customColors"
                                :key="`custom-${index}`"
                                class="swatch custom-swatch"
                                :class="{ selected: color === modelValue }"
                                :style="{ background: color }"
                                @click.stop="selectColor(color)"
                                @contextmenu.stop.prevent="deleteCustomColor(index)"
                            >
                                <button
                                    class="delete-btn"
                                    @click.stop="deleteCustomColor(index)"
                                    aria-label="Delete color"
                                >×</button>
                            </div>
                            <!-- Single Add Color Button (only if less than 8 colors) -->
                            <div
                                v-if="customColors.length < 8"
                                class="swatch add-color-btn"
                                @click.stop="openColorInput"
                                :class="{ active: showColorInput }"
                            >
                                <span class="add-icon">+</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Color Input -->
                <div v-if="showColorInput" class="color-input-container">
                    <input
                        type="color"
                        v-model="newColor"
                        @input="addCustomColor"
                        class="color-input"
                        ref="colorInputRef"
                    />
                </div>
            </div>

            <!-- Paint Button attached to Color Picker -->
            <div class="paint-button-attached">
                <button
                    class="paint-btn-attached"
                    :class="{ active: paintMode }"
                    :disabled="disabled"
                    @click.stop="$emit('close')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="paint-icon">
                        <path d="M240-120q-45 0-89-22t-71-58q26 0 53-20.5t27-59.5q0-50 35-85t85-35q50 0 85 35t35 85q0 66-47 113t-113 47Zm230-240L360-470l358-358q11-11 27.5-11.5T774-828l54 54q12 12 12 28t-12 28L470-360Z"></path>
                    </svg>
                    <span class="paint-text">Paint</span>
                    <span v-if="pixelCount !== null" class="pixel-count-badge">{{ pixelCount }}</span>
                </button>
            </div>

        </div>
    </Transition>
</template>

<style scoped>
/* ===== BASE / MOBILE FIRST ===== */

#colorPicker {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background: #fff;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    z-index: 2;
    display: flex;
    flex-direction: column;
    pointer-events: auto;
    max-height: 40vh;
    overflow: hidden;
}

/* Hint Banner */
.paint-hint-banner {
    position: absolute;
    top: -50px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    border: 2px solid rgba(0, 0, 0, 0.15);
    border-radius: 20px;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #1a1a1a;
    white-space: nowrap;
    pointer-events: none;
    font-family: 'pixel art', monospace;
}

.hint-icon {
    width: 16px;
    height: 16px;
}

.paint-hint-banner kbd {
    background: rgba(0, 0, 0, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

.touchscreen-hidden {
    display: none;
}

/* Header */
.picker-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.header-btn {
    background: transparent;
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1a1a1a;
}

.header-btn .icon {
    width: 20px;
    height: 20px;
}

.header-title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-title h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    font-family: 'pixel art', monospace;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 6px;
}

.color-preview {
    width: 20px;
    height: 20px;
    border: 2px solid #1a1a1a;
    border-radius: 4px;
}

.close-btn {
    margin-left: auto;
}

/* Color Grid */
.color-grid-container {
    padding: 12px;
    overflow-y: auto;
}

.palette-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.main-palette-section,
.custom-palette-section {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.palette-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: rgba(0, 0, 0, 0.5);
    font-family: 'pixel art', monospace;
}

/* Both grids: 8 columns on mobile */
.palette-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 6px;
    background: rgba(0, 0, 0, 0.04);
    padding: 8px;
    border-radius: 10px;
}

/* Swatches */
.swatch {
    aspect-ratio: 1;
    border-radius: 4px;
    border: 2px solid transparent;
    cursor: pointer;
    box-sizing: border-box;
    transition: transform 0.15s, box-shadow 0.15s;
}

.swatch:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 1;
}

.swatch.selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.3);
}

/* Custom swatch delete button */
.custom-swatch {
    position: relative;
}

.delete-btn {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: none;
    background: #ef4444;
    color: #fff;
    font-size: 12px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.15s;
}

.custom-swatch:hover .delete-btn {
    opacity: 1;
}

/* Add color button */
.add-color-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.06);
    border: 2px dashed rgba(0, 0, 0, 0.2);
}

.add-color-btn:hover {
    background: rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.4);
}

.add-color-btn.active {
    background: rgba(37, 99, 235, 0.15);
    border-color: #2563eb;
    border-style: solid;
}

.add-icon {
    font-size: 20px;
    color: rgba(0, 0, 0, 0.5);
    font-weight: 700;
}

/* Color Input */
.color-input-container {
    margin-top: 10px;
}

.color-input {
    width: 100%;
    height: 44px;
    border: 2px solid rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    cursor: pointer;
}

/* Paint Button */
.paint-button-attached {
    position: absolute;
    bottom: -56px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    pointer-events: auto;
}

.paint-btn-attached {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    border: 2px solid #1d4ed8;
    background: #3b82f6;
    color: #fff;
    font-weight: 600;
    font-family: 'pixel art', monospace;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 4px 0 #1d4ed8;
    transition: transform 0.1s, box-shadow 0.1s;
}

.paint-btn-attached:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #1d4ed8;
}

.paint-btn-attached:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 2px 0 #1d4ed8;
}

.paint-btn-attached.active {
    background: #22c55e;
    border-color: #16a34a;
    box-shadow: 0 4px 0 #16a34a;
}

.paint-btn-attached:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.paint-btn-attached .paint-icon {
    width: 18px;
    height: 18px;
}

.paint-btn-attached .paint-text {
    font-size: 14px;
}

.paint-btn-attached .pixel-count-badge {
    background: rgba(255, 255, 255, 0.25);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    min-width: 24px;
    text-align: center;
}

/* Slide Animation */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(200%);
}

/* ===== DESKTOP (769px+) ===== */

@media (min-width: 769px) {
    #colorPicker {
        left: 0;
        right: 0;
        width: 100%;
    }

    .touchscreen-hidden {
        display: inline;
    }

    .palette-wrapper {
        flex-direction: row;
        gap: 16px;
        align-items: flex-start;
    }

    .main-palette-section {
        flex: 3;
    }

    .custom-palette-section {
        flex: 1;
    }

    /* Desktop: main palette 24 cols (1 row) */
    .main-palette {
        grid-template-columns: repeat(24, 1fr);
        gap: 4px;
        padding: 6px;
    }

    /* Desktop: custom palette 8 cols (1 row) */
    .custom-grid {
        grid-template-columns: repeat(8, 1fr);
        gap: 4px;
        padding: 6px;
    }

}
</style>
