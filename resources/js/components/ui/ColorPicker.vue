<template>
    <div id="colorPicker" @click.stop>
        <!-- Standard Palette -->
        <div class="palette-grid">
            <div
                v-for="color in palette"
                :key="color"
                class="swatch"
                :class="{ selected: color === modelValue }"
                :style="{ background: color }"
                @click.stop="$emit('update:modelValue', color)"
            ></div>
        </div>
        
        <!-- Custom Palette -->
        <div class="custom-palette-section">
            <div class="palette-grid custom-grid">
                <div
                    v-for="(color, index) in customColors"
                    :key="`custom-${index}`"
                    class="swatch custom-swatch"
                    :class="{ selected: color === modelValue }"
                    :style="{ background: color }"
                    @click.stop="$emit('update:modelValue', color)"
                    @contextmenu.stop.prevent="deleteCustomColor(index)"
                >
                    <button 
                        class="delete-btn"
                        @click.stop="deleteCustomColor(index)"
                        aria-label="Delete color"
                    >×</button>
                </div>
                <!-- Add Color Button -->
                <div
                    v-if="customColors.length < 8"
                    class="swatch add-color-btn"
                    @click.stop="showColorInput = !showColorInput"
                    :class="{ active: showColorInput }"
                >
                    <span class="add-icon">+</span>
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
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const STORAGE_KEY = 'colorPicker:selectedColor'
const CUSTOM_COLORS_KEY = 'colorPicker:customColors'

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

// Load custom colors from localStorage
const customColors = ref([])
try {
    const stored = localStorage.getItem(CUSTOM_COLORS_KEY)
    if (stored) {
        const parsed = JSON.parse(stored)
        if (Array.isArray(parsed) && parsed.length <= 8) {
            customColors.value = parsed
        }
    }
} catch (e) {
    // ignore storage errors
}

const showColorInput = ref(false)
const newColor = ref('#000000')
const colorInputRef = ref(null)

// Save custom colors to localStorage
function saveCustomColors() {
    try {
        localStorage.setItem(CUSTOM_COLORS_KEY, JSON.stringify(customColors.value))
    } catch (e) {
        // ignore storage errors
    }
}

// Add custom color
function addCustomColor() {
    const color = newColor.value.toUpperCase()
    
    // Don't add if already exists or if palette is full
    if (customColors.value.includes(color) || customColors.value.length >= 8) {
        return
    }
    
    // Don't add if it's already in standard palette
    if (palette.includes(color)) {
        return
    }
    
    customColors.value.push(color)
    saveCustomColors()
    
    // Select the new color
    emit('update:modelValue', color)
    
    // Hide input and reset
    showColorInput.value = false
    newColor.value = '#000000'
}

// Delete custom color
function deleteCustomColor(index) {
    const deletedColor = customColors.value[index]
    customColors.value.splice(index, 1)
    saveCustomColors()
    
    // If deleted color was selected, switch to black
    if (deletedColor === props.modelValue) {
        emit('update:modelValue', '#000000')
    }
}

// loads color black
let selectedColor = '#000000'
try {
    const storedColor = localStorage.getItem(STORAGE_KEY)
    if (storedColor) selectedColor = storedColor
} catch {}

const color = ref(selectedColor)

if (selectedColor !== props.modelValue) {
    emit('update:modelValue', selectedColor)
}

watch(
    () => props.modelValue,
    v => {
        color.value = v
        try {
            if (v) localStorage.setItem(STORAGE_KEY, v)
        } catch (e) {
            // ignore storage errors
        }
    }
)
</script>

<style scoped>
#colorPicker {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    pointer-events: auto;
    padding: 5px;
    margin: 0;
    width: 260px;
    image-rendering: pixelated;
    max-width: calc(100vw - 20px);
}

.palette-grid {
    display: grid;
    gap: 2px;
    grid-template-columns: repeat(8, 1fr);
    background: rgba(0, 0, 0, 0.5);
    padding: 2px;
    margin-bottom: 5px;
}

.custom-palette-section {
    margin-top: 5px;
}

.custom-grid {
    margin-bottom: 0;
}

.swatch {
    width: 100%;
    aspect-ratio: 1;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
    position: relative;
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

.custom-swatch {
    position: relative;
}

.custom-swatch:hover .delete-btn {
    opacity: 1;
}

.delete-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    padding: 0;
    z-index: 10;
}

.delete-btn:hover {
    background: rgba(255, 0, 0, 0.8);
    transform: scale(1.1);
}

.add-color-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 2px dashed rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-color-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

.add-color-btn.active {
    background: rgba(255, 255, 255, 0.3);
    border-color: #667eea;
}

.add-icon {
    font-size: 20px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: bold;
}

.color-input-container {
    margin-top: 5px;
    display: flex;
    justify-content: center;
}

.color-input {
    width: 100%;
    height: 40px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    cursor: pointer;
    background: rgba(0, 0, 0, 0.5);
}
</style>
