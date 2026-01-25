<script setup>
import { ref } from 'vue'
import GroupModal from '@/components/group/GroupModal.vue'
import LeaderboardModal from '@/components/leaderboard/LeaderboardModal.vue'
import SavedLocationsModal from '@/components/ui/SavedLocationsModal.vue'

const props = defineProps({
    hidden: {
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

const emit = defineEmits(['navigateToLocation'])

const showGroupModal = ref(false)
const showLeaderboardModal = ref(false)
const showSavedLocationsModal = ref(false)

function openGroupModal() {
    showGroupModal.value = true
}

function openLeaderboardModal() {
    showLeaderboardModal.value = true
}

function openSavedLocationsModal() {
    showSavedLocationsModal.value = true
}

function handleNavigateToLocation(location) {
    emit('navigateToLocation', location)
    showSavedLocationsModal.value = false
}
</script>

<template>
    <div class="floating-buttons" :class="{ 'mobile-hidden': hidden }">
        <button
            class="action-btn leaderboard-btn"
            @click="openLeaderboardModal"
            title="Leaderboard"
        >
            <span class="btn-icon">
                <svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22,7H16.333V4a1,1,0,0,0-1-1H8.667a1,1,0,0,0-1,1v7H2a1,1,0,0,0-1,1v8a1,1,0,0,0,1,1H22a1,1,0,0,0,1-1V8A1,1,0,0,0,22,7ZM7.667,19H3V13H7.667Zm6.666,0H9.667V5h4.666ZM21,19H16.333V9H21Z"/>
                </svg>
            </span>
            <span class="btn-label">Leaderboard</span>
        </button>

        <button
            class="action-btn groups-btn"
            @click="openGroupModal"
            title="Manage Groups"
        >
            <span class="btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </span>
            <span class="btn-label">Groups</span>
        </button>

        <button
            class="action-btn locations-btn"
            @click="openSavedLocationsModal"
            title="Saved Locations"
        >
            <span class="btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
            </span>
            <span class="btn-label">Locations</span>
        </button>
    </div>

    <GroupModal v-model="showGroupModal" />
    <LeaderboardModal v-model="showLeaderboardModal" />
    <SavedLocationsModal
        v-model="showSavedLocationsModal"
        :mapCenter="mapCenter"
        :mapZoom="mapZoom"
        @navigateToLocation="handleNavigateToLocation"
    />
</template>

<style scoped>
.floating-buttons {
    position: fixed;
    top: 90px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 1;
    pointer-events: none;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: auto;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    font-family: 'pixel art', monospace;
}

.action-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    border-color: rgba(0, 0, 0, 0.12);
}

.action-btn:active {
    transform: translateY(0) scale(0.98);
}

.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.btn-icon svg {
    width: 20px;
    height: 20px;
}

.leaderboard-btn .btn-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.leaderboard-btn:hover .btn-icon {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
}

.groups-btn .btn-icon {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}

.groups-btn:hover .btn-icon {
    background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
}

.locations-btn .btn-icon {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.locations-btn:hover .btn-icon {
    background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
}

.btn-label {
    font-size: 14px;
    font-weight: 600;
    color: #1e1e1e;
    white-space: nowrap;
}

@media (max-width: 640px) {
    .floating-buttons {
        top: 80px;
        right: 12px;
        gap: 8px;
    }

    .floating-buttons.mobile-hidden {
        display: none !important;
    }

    .action-btn {
        padding: 10px 14px;
        border-radius: 12px;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
    }

    .btn-icon svg {
        width: 18px;
        height: 18px;
    }

    .btn-label {
        display: none;
    }
}
</style>
