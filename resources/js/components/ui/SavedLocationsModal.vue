<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useSavedLocations } from '@/composables/useSavedLocations'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import SaveLocationModal from './SaveLocationModal.vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    mapCenter: {
        type: Object,
        default: null
    },
    mapZoom: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'navigateToLocation'])

const { locations, loading, load, remove, getShareUrl } = useSavedLocations()
const { isAuthenticated, user } = useAuth()
const { showToast } = useToast()

const showSaveModal = ref(false)

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

watch([isOpen, user], async ([newIsOpen, newUser]) => {
    if (newIsOpen && newUser) {
        await load()
    }
})

onMounted(async () => {
    if (isAuthenticated()) {
        await load()
    }
})

async function handleDelete(id, event) {
    event.stopPropagation()
    await remove(id)
}

function handleNavigate(location) {
    emit('navigateToLocation', {
        longitude: parseFloat(location.longitude),
        latitude: parseFloat(location.latitude),
        zoom: location.zoom ? parseFloat(location.zoom) : null
    })
    isOpen.value = false
}

async function handleShare(location, event) {
    event.stopPropagation()
    const url = getShareUrl(location)
    if (url) {
        await navigator.clipboard.writeText(url)
        showToast('Share link copied!', 'success')
    }
}

function openSaveModal() {
    if (!isAuthenticated()) {
        showToast('Please sign in to save locations', 'error')
        return
    }
    showSaveModal.value = true
}

function handleSaved() {
    showSaveModal.value = false
    load()
}
</script>

<template>
    <Transition name="fade">
        <div v-if="isOpen" class="modal-overlay" @click="isOpen = false">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h2>Saved Locations</h2>
                    <button @click="isOpen = false" class="close-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <button @click="openSaveModal" class="save-location-btn" :disabled="loading">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/>
                        </svg>
                        <span>Save Current Location</span>
                    </button>

                    <div v-if="loading" class="loading-state">
                        <span>Loading...</span>
                    </div>

                    <div v-else-if="!isAuthenticated()" class="auth-prompt">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                        </svg>
                        <p>Sign in to save and access locations</p>
                    </div>

                    <div v-else-if="locations.length === 0" class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        <p>No saved locations yet</p>
                        <p class="hint">Save your current location to get started</p>
                    </div>

                    <div v-else class="locations-list">
                        <div
                            v-for="location in locations"
                            :key="location.id"
                            class="location-item"
                            @click="handleNavigate(location)"
                        >
                            <div class="location-info">
                                <div class="location-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="location-details">
                                    <strong>{{ location.name || 'Unnamed Location' }}</strong>
                                    <span>{{ parseFloat(location.latitude).toFixed(4) }}, {{ parseFloat(location.longitude).toFixed(4) }}</span>
                                </div>
                            </div>
                            <div class="location-actions">
                                <button
                                    @click="handleShare(location, $event)"
                                    class="action-btn share-btn"
                                    title="Copy share link"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M15.75 4.5a3 3 0 11.825 2.066l-8.421 4.679a3.002 3.002 0 010 1.51l8.421 4.679a3 3 0 11-.729 1.31l-8.421-4.678a3 3 0 110-4.132l8.421-4.679a3 3 0 01-.096-.755z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <button
                                    @click="handleDelete(location.id, $event)"
                                    class="action-btn delete-btn"
                                    title="Delete location"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 101.499.058l-.347 9a.75.75 0 00-1.499-.058l.347-9z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <SaveLocationModal
        v-model="showSaveModal"
        :longitude="mapCenter?.lng || 0"
        :latitude="mapCenter?.lat || 0"
        :zoom="mapZoom"
        @saved="handleSaved"
    />
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
    max-width: 520px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    font-family: 'pixel art', monospace;
    display: flex;
    flex-direction: column;
    pointer-events: auto;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
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
    overflow-y: auto;
    flex: 1;
}

.save-location-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: white;
    border: 2px dashed rgba(0, 0, 0, 0.15);
    border-radius: 12px;
    width: 100%;
    cursor: pointer;
    font-family: 'pixel art', monospace;
    font-size: 14px;
    font-weight: 500;
    color: #1e1e1e;
    transition: all 0.2s;
    margin-bottom: 20px;
}

.save-location-btn:hover:not(:disabled) {
    background: #f5f5f5;
    border-color: #2563eb;
    border-style: solid;
    color: #2563eb;
}

.save-location-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.save-location-btn svg {
    width: 20px;
    height: 20px;
    color: #666;
}

.save-location-btn:hover:not(:disabled) svg {
    color: #2563eb;
}

.loading-state,
.auth-prompt,
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.auth-prompt svg,
.empty-state svg {
    width: 48px;
    height: 48px;
    color: #999;
    margin: 0 auto 16px;
    display: block;
}

.auth-prompt p,
.empty-state p {
    margin: 0;
    font-size: 14px;
}

.empty-state .hint {
    margin-top: 8px;
    font-size: 12px;
    color: #999;
}

.locations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.location-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    cursor: pointer;
    transition: all 0.2s;
}

.location-item:hover {
    background: #f5f5f5;
    transform: translateX(4px);
    border-color: rgba(0, 0, 0, 0.1);
}

.location-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.location-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #dbeafe;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.location-icon svg {
    width: 22px;
    height: 22px;
}

.location-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.location-details strong {
    font-size: 15px;
    font-weight: 600;
    color: #1e1e1e;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.location-details span {
    font-size: 12px;
    color: #666;
    font-family: monospace;
}

.location-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.action-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.action-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1e1e1e;
}

.action-btn.delete-btn:hover {
    background: #fee2e2;
    color: #dc2626;
}

.action-btn.share-btn:hover {
    background: #dbeafe;
    color: #2563eb;
}

.action-btn svg {
    width: 18px;
    height: 18px;
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
