<script setup>
import {computed, ref, watch} from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import GroupLinkModal from './GroupLinkModal.vue'
import CreateGroupModal from './CreateGroupModal.vue'

const { fetchGroup, group } = useAuth()
const { showToast } = useToast()

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
        // Check for invite code in URL
        const urlParams = new URLSearchParams(window.location.search)
        const inviteCode = urlParams.get('invite')
        if (inviteCode && !hasGroup.value) {
            joinGroupByCode(inviteCode)
        }
    }
})

function close() {
    emit('update:modelValue', false)
}

const isSharing = ref(false)
const isLeaving = ref(false)
const isJoining = ref(false)
const showGroupLinkModal = ref(false)
const showCreateModal = ref(false)

const inviteCode = computed(() =>
    group.value?.invite_code ?? null
)

const inviteUrl = computed(() => {
    if (!inviteCode.value) return 'https://pixel-art.design'
    return `https://pixel-art.design?invite=${inviteCode.value}`
})

function openShareModal() {
    if (!group.value || !inviteCode.value) return
    showGroupLinkModal.value = true
}

const showLeaveConfirm = ref(false)

function openLeaveConfirm() {
    showLeaveConfirm.value = true
}

function closeLeaveConfirm() {
    showLeaveConfirm.value = false
}

async function leaveGroup() {
    if (!group.value) return
    closeLeaveConfirm()
    isLeaving.value = true
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const res = await fetch('/api/group/leave', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {})
            }
        })
        if (!res.ok) {
            const error = await res.json().catch(() => ({ error: 'Failed to leave group' }))
            throw new Error(error.error || 'Failed to leave group')
        }
        showToast('Left group successfully', 'success')
        await fetchGroup()
    } catch (e) {
        console.error(e)
        showToast(e.message || 'Could not leave group', 'error')
    } finally {
        isLeaving.value = false
    }
}

async function joinGroupByCode(code) {
    if (!code) return
    isJoining.value = true
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const res = await fetch('/api/group/join', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {})
            },
            body: JSON.stringify({ invite_code: code })
        })
        if (!res.ok) {
            const error = await res.json().catch(() => ({ error: 'Failed to join group' }))
            throw new Error(error.error || 'Failed to join group')
        }
        showToast('Joined group successfully', 'success')
        // Remove invite param from URL
        const url = new URL(window.location.href)
        url.searchParams.delete('invite')
        window.history.replaceState({}, '', url.toString())
        await fetchGroup()
    } catch (e) {
        console.error(e)
        showToast(e.message || 'Could not join group', 'error')
    } finally {
        isJoining.value = false
    }
}

function openCreateModal() {
    showCreateModal.value = true
}

function handleGroupCreated() {
    fetchGroup()
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

                    <div class="modal-header-bar">
                        <h2>
                            {{ group?.name || 'Group name not found' }}
                            <span v-if="inviteCode" class="invite-code">Code: {{ inviteCode }}</span>
                        </h2>
                        <div class="header-actions">
                            <button class="btn btn-secondary" @click="openShareModal" :disabled="!inviteCode">
                                Share
                            </button>
                            <button class="btn btn-danger" @click="openLeaveConfirm" :disabled="isLeaving">
                                <span v-if="!isLeaving">Leave group</span>
                                <span v-else>Leaving...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="group-details">
                    <div class="group-details-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                        </svg>
                        <span>Pixels painted: <strong>{{ group?.pixels || 0 }}</strong></span>
                    </div>
                    <div class="group-details-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
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
                    <p class="no-members" v-else>No members yet</p>
                </div>
            </div>

            <div v-else class="no-group-container">
                <div class="no-group-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="group-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                    <h3 class="group-title">Group</h3>
                </div>
                <div class="no-group-content">
                    <span class="no-group-text">You are not in a group:</span>
                    <span class="get-invited-text">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="email-icon">
                            <path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200ZM160-640v-80 480-400Z"></path>
                        </svg>
                        Get invited to a group
                    </span>
                    <div class="divider-container">
                        <div class="divider">OR</div>
                    </div>
                    <button class="btn-create-group" @click="openCreateModal">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor" class="plus-icon">
                            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"></path>
                        </svg>
                        Create a group
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showLeaveConfirm" class="modal-overlay" @click="closeLeaveConfirm">
        <div class="confirm-modal" @click.stop>
            <h3>Leave Group?</h3>
            <p>Are you sure you want to leave {{ group?.name }}? This action cannot be undone.</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary" @click="closeLeaveConfirm">Cancel</button>
                <button class="btn btn-danger" @click="leaveGroup" :disabled="isLeaving">
                    <span v-if="!isLeaving">Leave</span>
                    <span v-else>Leaving...</span>
                </button>
            </div>
        </div>
    </div>

    <GroupLinkModal
        v-model:open="showGroupLinkModal"
        :inviteLink="inviteUrl"
    />

    <CreateGroupModal
        v-model="showCreateModal"
        @created="handleGroupCreated"
    />
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
    border-radius: 16px;
    padding: 24px;
    max-width: 768px;
    width: 90%;
    height: 88%;
    overflow: hidden;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
}

@media (max-width: 640px) {
    .modal-content {
        width: 100%;
        height: 100%;
        border-radius: 0;
        padding: 20px 12px;
    }
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

.modal-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.modal-header-bar h2 {
    margin: 0;
    color: #1e1e1e;
    font-size: 24px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
}

.invite-code {
    background: #f3f4f6;
    color: #111827;
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 8px;
}

.group-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 32px;
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
    border-bottom: 1px solid #e5e5e5;
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

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    border-radius: 8px;
    padding: 10px 14px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background-color 0.15s ease;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f3f4f6;
    color: #111827;
}
.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-danger {
    background: #fee2e2;
    color: #b91c1c;
}
.btn-danger:hover {
    background: #fecaca;
}

.btn-primary {
    background: #111827;
    color: #fff;
}
.btn-primary:hover {
    background: #0b0f17;
}

.no-group-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.no-group-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}

.group-icon {
    width: 20px;
    height: 20px;
    color: #666;
}

.group-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1e1e1e;
}

.no-group-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    min-height: 0;
}

.no-group-text {
    color: rgba(17, 24, 39, 0.8);
    font-size: 16px;
}

.get-invited-text {
    margin-top: 32px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 20px;
    font-weight: 600;
    color: #1e1e1e;
}

.email-icon {
    width: 20px;
    height: 20px;
}

.divider-container {
    display: flex;
    width: 100%;
    justify-content: center;
    margin: 8px 0;
}

.divider {
    width: 100%;
    max-width: 384px;
    display: flex;
    align-items: center;
    text-align: center;
    color: #999;
    font-size: 14px;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e5e5e5;
}

.divider::before {
    margin-right: 16px;
}

.divider::after {
    margin-left: 16px;
}

.btn-create-group {
    margin-bottom: 24px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #f3f4f6;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #1e1e1e;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-create-group:hover:not(:disabled) {
    background: #e5e7eb;
}

.btn-create-group:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.plus-icon {
    width: 24px;
    height: 24px;
}

.confirm-modal {
    background: white;
    border-radius: 16px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.confirm-modal h3 {
    margin: 0 0 12px 0;
    font-size: 20px;
    font-weight: 600;
    color: #1e1e1e;
}

.confirm-modal p {
    margin: 0 0 24px 0;
    color: #666;
    font-size: 14px;
    line-height: 1.5;
}

.confirm-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}
</style>
