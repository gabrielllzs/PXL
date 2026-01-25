<script setup>
import { ref, computed, watch } from 'vue'
import { useSavedLocations } from '@/composables/useSavedLocations'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    longitude: {
        type: Number,
        required: true
    },
    latitude: {
        type: Number,
        required: true
    },
    zoom: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'saved'])

const { save, locations } = useSavedLocations()
const { isAuthenticated } = useAuth()

const name = ref('')
const saving = ref(false)

const canSave = computed(() => {
    if (!isAuthenticated()) return false
    return locations.value.length < 3
})

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        name.value = ''
    }
})

async function handleSave() {
    if (!isAuthenticated() || !canSave.value) return

    saving.value = true
    const result = await save(name.value.trim() || null, props.longitude, props.latitude, props.zoom)
    saving.value = false

    if (result) {
        isOpen.value = false
        emit('saved', result)
    }
}

function handleClose() {
    isOpen.value = false
}
</script>

<template>
    <Transition name="fade">
        <div v-if="isOpen" class="modal-overlay" @click="handleClose">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h2>Save Location</h2>
                    <button @click="handleClose" class="close-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="!isAuthenticated()" class="auth-required">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                        </svg>
                        <p>Please sign in to save locations</p>
                    </div>

                    <div v-else-if="!canSave" class="limit-reached">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                        </svg>
                        <p>You can only save up to 3 locations</p>
                        <p class="hint">Delete an existing location to save a new one</p>
                    </div>

                    <div v-else class="save-form">
                        <div class="form-group">
                            <label for="location-name">Location Name (optional)</label>
                            <input
                                id="location-name"
                                v-model="name"
                                type="text"
                                placeholder="e.g., My Artwork, Favorite Spot"
                                maxlength="255"
                                @keyup.enter="handleSave"
                            />
                        </div>

                        <div class="location-info">
                            <div class="info-item">
                                <span class="label">Coordinates:</span>
                                <span class="value">{{ latitude.toFixed(6) }}, {{ longitude.toFixed(6) }}</span>
                            </div>
                            <div v-if="zoom" class="info-item">
                                <span class="label">Zoom:</span>
                                <span class="value">{{ zoom.toFixed(2) }}</span>
                            </div>
                        </div>

                        <button
                            @click="handleSave"
                            :disabled="saving"
                            class="save-btn"
                        >
                            <span v-if="saving">Saving...</span>
                            <span v-else>Save Location</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    pointer-events: auto;
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    font-family: 'pixel art', monospace;
    pointer-events: auto;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1e1e1e;
}

.close-btn {
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
}

.close-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1e1e1e;
}

.close-btn svg {
    width: 20px;
    height: 20px;
}

.modal-body {
    padding: 24px;
}

.auth-required,
.limit-reached {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.auth-required svg,
.limit-reached svg {
    width: 48px;
    height: 48px;
    color: #999;
    margin: 0 auto 16px;
    display: block;
}

.auth-required p,
.limit-reached p {
    margin: 0;
    font-size: 14px;
}

.limit-reached .hint {
    margin-top: 8px;
    font-size: 12px;
    color: #999;
}

.save-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input {
    padding: 12px 16px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'pixel art', monospace;
    transition: border-color 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: #2563eb;
}

.location-info {
    background: #f5f5f5;
    border-radius: 10px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.info-item .label {
    color: #666;
    font-weight: 500;
}

.info-item .value {
    color: #1e1e1e;
    font-family: monospace;
}

.save-btn {
    padding: 14px 24px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'pixel art', monospace;
    cursor: pointer;
    transition: all 0.2s;
}

.save-btn:hover:not(:disabled) {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-active .modal-content,
.fade-leave-active .modal-content {
    transition: transform 0.2s, opacity 0.2s;
}

.fade-enter-from .modal-content,
.fade-leave-to .modal-content {
    transform: scale(0.95);
    opacity: 0;
}
</style>
