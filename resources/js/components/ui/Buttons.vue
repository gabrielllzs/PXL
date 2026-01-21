<script setup>
import { ref } from 'vue'
import GroupModal from '@/components/group/GroupModal.vue'
import LeaderboardModal from '@/components/leaderboard/LeaderboardModal.vue'

const showGroupModal = ref(false)
const showLeaderboardModal = ref(false)

function openGroupModal() {
    showGroupModal.value = true
}

function openLeaderboardModal() {
    showLeaderboardModal.value = true
}

</script>


<template>
    <div class="container">
        <div class="row">
            <div class="short-button" @click="openLeaderboardModal" title="Leaderboard">
                <svg fill="currentColor"  viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22,7H16.333V4a1,1,0,0,0-1-1H8.667a1,1,0,0,0-1,1v7H2a1,1,0,0,0-1,1v8a1,1,0,0,0,1,1H22a1,1,0,0,0,1-1V8A1,1,0,0,0,22,7ZM7.667,19H3V13H7.667Zm6.666,0H9.667V5h4.666ZM21,19H16.333V9H21Z"/>
                </svg>
            </div>
            <div class="short-button" @click="openGroupModal" title="Manage Groups">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </div>
        </div>
    </div>

    <GroupModal
        v-model="showGroupModal"
    />

    <LeaderboardModal
        v-model="showLeaderboardModal"
    />
</template>

<style scoped>
.container {
    position: fixed;
    top: 72px;
    right: 16px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.row {
}

.short-button {
    background: white;
    border-radius: 8px;
    padding: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: all;
    margin-bottom: 20px;
}

.short-button:hover {
    transform: translateY(-2px);
}

.short-button svg {
    width: 24px;
    height: 24px;
    color: black;
}

@media (max-width: 640px) {
    .container {
        top: 70px;
    }
}
</style>
