<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const props = defineProps({
    open: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'banned'])

const users = ref([])
const anonymousVisitors = ref([])
const showVisitorPicker = ref(false)
const visitorSearchQuery = ref('')

const banForm = ref({
    user_id: null,
    username: '',
    visitor_id: '',
    ip_address: '',
    reason: '',
    hide_pixels: false,
    is_permanent: true,
    banned_until: ''
})

const filteredVisitors = computed(() => {
    const allItems = []

    users.value.forEach(user => {
        allItems.push({
            type: 'user',
            user_id: user.id,
            username: user.username,
            email: user.email,
            visitor_id: user.visitor_id || null,
            id: `user-${user.id}`
        })
    })

    anonymousVisitors.value.forEach(visitor => {
        allItems.push({
            type: 'visitor',
            visitor_id: visitor.visitor_id,
            ip_address: visitor.ip_address,
            id: `visitor-${visitor.visitor_id}`
        })
    })

    if (!visitorSearchQuery.value) return allItems.slice(0, 50)

    const q = visitorSearchQuery.value.toLowerCase()
    return allItems.filter(item => {
        if (item.type === 'user') {
            return item.username?.toLowerCase().includes(q) ||
                   item.email?.toLowerCase().includes(q)
        } else {
            return item.visitor_id?.toLowerCase().includes(q) ||
                   item.ip_address?.toLowerCase().includes(q)
        }
    }).slice(0, 50)
})

watch(() => props.open, async (isOpen) => {
    if (!isOpen) return
    resetForm()
    try {
        const res = await fetch('/users')
        const data = await res.json()
        users.value = data.users || []
        anonymousVisitors.value = data.anonymous_visitors || []
    } catch (_) {
        users.value = []
        anonymousVisitors.value = []
    }
}, { immediate: true })

function resetForm() {
    banForm.value = {
        user_id: null,
        username: '',
        visitor_id: '',
        ip_address: '',
        reason: '',
        hide_pixels: false,
        is_permanent: true,
        banned_until: ''
    }
    visitorSearchQuery.value = ''
    showVisitorPicker.value = false
}

function selectVisitor(item) {
    if (item.type === 'user') {
        banForm.value.user_id = item.user_id
        banForm.value.username = item.username || ''
        banForm.value.visitor_id = item.visitor_id || ''
        banForm.value.ip_address = ''
    } else {
        banForm.value.user_id = null
        banForm.value.username = ''
        banForm.value.visitor_id = item.visitor_id || ''
        banForm.value.ip_address = item.ip_address || ''
    }
    showVisitorPicker.value = false
    visitorSearchQuery.value = ''
}

function clearSelection() {
    banForm.value.user_id = null
    banForm.value.username = ''
    banForm.value.visitor_id = ''
    banForm.value.ip_address = ''
}

function close() {
    emit('close')
}

async function submitBan() {
    if (!banForm.value.reason.trim()) {
        showToast('Reason is required', 'error')
        return
    }

    const hasUser = !!banForm.value.user_id
    const hasVisitor = !!(banForm.value.visitor_id?.trim() || banForm.value.ip_address?.trim())
    if (!hasUser && !hasVisitor) {
        showToast('Please provide a user (use Browse) or Visitor ID / IP Address', 'error')
        return
    }

    if (!banForm.value.is_permanent && !banForm.value.banned_until) {
        showToast('Please select an end date for temporary ban', 'error')
        return
    }

    const form = new FormData()
    if (banForm.value.user_id) form.append('user_id', banForm.value.user_id)
    if (banForm.value.visitor_id?.trim()) form.append('visitor_id', banForm.value.visitor_id)
    if (banForm.value.ip_address?.trim()) form.append('ip_address', banForm.value.ip_address)
    form.append('reason', banForm.value.reason)
    form.append('hide_pixels', banForm.value.hide_pixels ? 1 : 0)
    form.append('is_permanent', banForm.value.is_permanent ? 1 : 0)

    if (!banForm.value.is_permanent) {
        form.append('banned_until', banForm.value.banned_until)
    }

    try {
        const response = await fetch('/ban-visitors', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: form
        })

        if (response.ok) {
            const target = banForm.value.username || banForm.value.visitor_id || banForm.value.ip_address || 'User'
            showToast(`${target} banned successfully`, 'success')
            emit('banned')
            close()
        } else {
            showToast('Failed to ban user', 'error')
        }
    } catch (e) {
        showToast('Error banning user: ' + (e.message || 'Unknown error'), 'error')
    }
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="modal-overlay" @click.self="close">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Ban User</h2>
                    <button type="button" class="close-btn" @click="close">✕</button>
                </div>

                <form @submit.prevent="submitBan" class="ban-form">
                    <div class="form-group">
                        <div class="input-header">
                            <label>User/Visitor</label>
                            <button
                                type="button"
                                @click="showVisitorPicker = !showVisitorPicker"
                                class="picker-toggle-btn"
                            >
                                {{ showVisitorPicker ? 'Hide' : 'Browse' }}
                            </button>
                        </div>

                        <div v-if="showVisitorPicker" class="visitor-picker">
                            <input
                                v-model="visitorSearchQuery"
                                type="text"
                                name="search-bar"
                                placeholder="Search..."
                                class="search-input"
                            />
                            <div class="visitor-list">
                                <div
                                    v-for="item in filteredVisitors"
                                    :key="item.id"
                                    class="visitor-item"
                                    @click="selectVisitor(item)"
                                >
                                    <div class="visitor-info">
                                        <span v-if="item.type === 'user'" class="badge-user">
                                            {{ item.username }}
                                        </span>
                                        <span v-else class="visitor-id">{{ item.visitor_id }}</span>
                                        <span v-if="item.type === 'user'" class="visitor-email">{{ item.email }}</span>
                                        <span v-else class="visitor-ip">{{ item.ip_address }}</span>
                                    </div>
                                    <button type="button" class="select-btn">Select</button>
                                </div>
                                <div v-if="filteredVisitors.length === 0" class="no-visitors">
                                    No users or visitors found
                                </div>
                            </div>
                        </div>

                        <div v-if="banForm.username" class="selected-user">
                            <span class="badge-user">{{ banForm.username }}</span>
                            <button type="button" class="clear-selection-btn" @click="clearSelection">Clear</button>
                        </div>
                        <input
                            v-else
                            id="visitor_id"
                            v-model="banForm.visitor_id"
                            type="text"
                            placeholder="Enter Visitor ID (or use Browse above)"
                        />
                    </div>

                    <div v-if="!banForm.username" class="form-group">
                        <label for="ip_address">IP Address</label>
                        <input
                            id="ip_address"
                            v-model="banForm.ip_address"
                            type="text"
                            placeholder="Enter IP Address (or use Browse above)"
                        />
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason *</label>
                        <textarea
                            id="reason"
                            v-model="banForm.reason"
                            placeholder="Enter the reason for banning..."
                            rows="4"
                            required
                        />
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input v-model="banForm.hide_pixels" type="checkbox" />
                            <span>Hide user's pixels</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Ban Duration</label>
                        <div class="radio-group">
                            <label>
                                <input v-model="banForm.is_permanent" type="radio" :value="true" />
                                <span>Permanent</span>
                            </label>
                            <label>
                                <input v-model="banForm.is_permanent" type="radio" :value="false" />
                                <span>Temporary</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="!banForm.is_permanent" class="form-group">
                        <label for="banned_until">Ban Until *</label>
                        <input
                            id="banned_until"
                            v-model="banForm.banned_until"
                            type="datetime-local"
                            :required="!banForm.is_permanent"
                        />
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" @click="close">Cancel</button>
                        <button type="submit" class="btn-submit">Ban User</button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.2s ease-out;
    font-family: 'pixel art', monospace;
}

.modal-content {
    background: #1e1e1e;
    border: 2px solid #444;
    border-radius: 8px;
    padding: 25px;
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.7);
    animation: slideUp 0.3s ease-out;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #333;
}

.modal-header h2 {
    margin: 0;
    color: #e0e0e0;
}

.close-btn {
    background: transparent;
    border: none;
    color: #888;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
    font-family: 'pixel art', monospace;
}

.close-btn:hover {
    background: #333;
    color: #fff;
}

.ban-form {
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
    color: #e0e0e0;
    font-size: 14px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group input[type="datetime-local"],
.form-group textarea {
    padding: 10px 12px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    font-family: 'pixel art', monospace;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ff4d4d;
}

.form-group textarea {
    resize: vertical;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: normal;
    padding: 10px;
    background: #252525;
    border-radius: 4px;
    transition: background 0.2s;
}

.radio-group label:hover {
    background: #2a2a2a;
}

.radio-group input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 10px;
}

.btn-cancel {
    padding: 12px 24px;
    background: #333;
    color: #ddd;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'pixel art', monospace;
}

.btn-cancel:hover {
    background: #444;
}

.btn-submit {
    padding: 12px 24px;
    background: #ff4d4d;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'pixel art', monospace;
}

.btn-submit:hover {
    background: #ff3333;
}

.input-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.picker-toggle-btn {
    padding: 6px 12px;
    background: #333;
    border: 1px solid #555;
    border-radius: 4px;
    color: #ddd;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
    font-family: 'pixel art', monospace;
}

.picker-toggle-btn:hover {
    background: #444;
}

.visitor-picker {
    margin-top: 10px;
    border: 1px solid #444;
    border-radius: 6px;
    background: #252525;
    padding: 12px;
    max-height: 400px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-sizing: border-box;
}

.visitor-picker .search-input {
    margin: 0;
    width: 100%;
    padding: 8px 12px;
    font-size: 13px;
    background: #1e1e1e;
    border: 1px solid #444;
    border-radius: 4px;
    color: white;
    font-family: 'pixel art', monospace;
    box-sizing: border-box;
    flex-shrink: 0;
}

.visitor-picker .search-input:focus {
    outline: none;
    border-color: #ff4d4d;
}

.visitor-list {
    overflow-y: auto;
    max-height: 320px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.visitor-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px;
    background: #1e1e1e;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.visitor-item:hover {
    background: #2a2a2a;
    border-color: #444;
}

.visitor-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
    min-width: 0;
}

.visitor-id {
    font-size: 12px;
    color: #bbb;
    font-family: 'pixel art', monospace;
    word-break: break-all;
}

.visitor-ip {
    font-size: 11px;
    color: #888;
    font-family: 'pixel art', monospace;
    word-break: break-all;
}

.visitor-email {
    font-size: 11px;
    color: #888;
    word-break: break-all;
}

.badge-user {
    display: inline-block;
    width: fit-content;
    padding: 4px 8px;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid #6366f1;
    color: #6366f1;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.select-btn {
    padding: 6px 14px;
    background: #ff4d4d;
    border: none;
    border-radius: 4px;
    color: white;
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
    font-family: 'pixel art', monospace;
}

.select-btn:hover {
    background: #ff3333;
    transform: scale(1.05);
}

.no-visitors {
    padding: 30px 20px;
    text-align: center;
    color: #666;
    font-size: 13px;
}

.selected-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
}

.clear-selection-btn {
    padding: 6px 12px;
    background: #333;
    border: 1px solid #555;
    border-radius: 4px;
    color: #ddd;
    cursor: pointer;
    font-size: 12px;
    margin-left: auto;
    transition: all 0.2s;
    font-family: 'pixel art', monospace;
}

.clear-selection-btn:hover {
    background: #444;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
