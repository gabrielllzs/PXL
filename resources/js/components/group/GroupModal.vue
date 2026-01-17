<script setup>
import {computed, ref, watch} from 'vue'
import { useAuth } from '@/composables/useAuth'

const { fetchGroup, group } = useAuth()

const hasGroup = computed(() => group.value !== null)

const emit = defineEmits(['update:modelValue'])

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

// Check group status when modal opens
watch(() => props.modelValue, async (isOpen) => {
    if (isOpen) {
        await fetchGroup()
    }
})

function close() {
    emit('update:modelValue', false)
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <button class="modal-close" @click="close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div v-if="hasGroup">
                <div class="modal-header">
                    <div class="modal-header-title">
                        <span class="modal-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </span>
                        <p class="modal-header__label">group</p>
                    </div>
                    <h2>{{ group?.name || 'Group name not found' }}</h2>
                </div>

                <div class="group-details">
                    <div class="group-details-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                        </svg>
                        <span>Pixels painted: <strong>{{ group?.pixels || 0 }}</strong></span>
                    </div>
                    <div class="group-details-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <span>Members: <strong>{{ group?.members?.length || 0 }}</strong></span>
                    </div>
                </div>

                <div class="group-members-leaderboard">
                    <h3>Members</h3>
                    <ul v-if="group?.members && group.members.length > 0">
                        <li v-for="member in group.members" :key="member.id">
                            <span class="member-name">{{ member.username }}</span>
                            <span class="member-pixels">{{ member.pixels || 0 }} pixels</span>
                        </li>
                    </ul>
                    <p v-else class="no-members">No members yet</p>
                </div>
            </div>

            <div v-else class="no-group">
                <div class="no-group-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <h2>No Group</h2>
                <p>You are not part of any group yet.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    pointer-events: auto;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    transition: color 0.2s;
    border-radius: 6px;
}

.modal-close:hover {
    color: #1e1e1e;
    background: rgba(0, 0, 0, 0.05);
}

.modal-close svg {
    width: 20px;
    height: 20px;
}

.modal-header {
    margin-bottom: 24px;
}

.modal-header-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.modal-header-icon {
    width: 20px;
    height: 20px;
    color: #666;
}

.modal-header__label {
    margin: 0;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: #666;
}

.modal-content h2 {
    margin: 0 0 20px 0;
    color: #1e1e1e;
    font-size: 24px;
    font-weight: 600;
}

.group-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 24px;
}

.group-details-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #555;
}

.group-details-item svg {
    width: 20px;
    height: 20px;
    color: #666;
    flex-shrink: 0;
}

.group-details-item strong {
    color: #1e1e1e;
    font-weight: 600;
}

.group-members-leaderboard {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e5e5e5;
}

.group-members-leaderboard h3 {
    margin: 0 0 16px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1e1e1e;
}

.group-members-leaderboard ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-members-leaderboard li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 8px;
    font-size: 14px;
}

.member-name {
    font-weight: 500;
    color: #1e1e1e;
}

.member-pixels {
    color: #666;
    font-size: 13px;
}

.no-members {
    text-align: center;
    color: #999;
    font-size: 14px;
    padding: 20px 0;
    margin: 0;
}

.no-group {
    text-align: center;
    padding: 20px 0;
}

.no-group-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    color: #ccc;
}

.no-group-icon svg {
    width: 100%;
    height: 100%;
}

.no-group h2 {
    margin: 0 0 8px 0;
    color: #1e1e1e;
    font-size: 22px;
}

.no-group p {
    margin: 0;
    color: #666;
    font-size: 14px;
}
</style>
