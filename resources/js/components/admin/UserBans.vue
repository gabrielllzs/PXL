<script setup>
import { ref, computed, onMounted } from 'vue'
import UserBansModal from './UserBansModal.vue'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const banned = ref([])
const loading = ref(true)
const showBanModal = ref(false)
const searchQuery = ref('')

onMounted(loadBanned)

async function loadBanned() {
    try {
        const res = await fetch('/banned-visitors')
        banned.value = await res.json()
    } finally {
        loading.value = false
    }
}

const filteredBanned = computed(() => {
    if (!searchQuery.value) return banned.value
    const q = searchQuery.value.toLowerCase()
    return banned.value.filter(b =>
        b.visitor_id?.toLowerCase().includes(q) ||
        b.ip_address?.toLowerCase().includes(q) ||
        b.reason?.toLowerCase().includes(q) ||
        b.user?.username?.toLowerCase().includes(q)
    )
})

const totalBans = computed(() => banned.value.length)
const permanentBans = computed(() => banned.value.filter(b => b.is_permanent).length)
const temporaryBans = computed(() => banned.value.filter(b => !b.is_permanent).length)

function openBanModal() {
    showBanModal.value = true
}

function closeBanModal() {
    showBanModal.value = false
}

function onBanned() {
    loadBanned()
}

async function unbanUser(banId) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const response = await fetch(`/unban/${banId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'include'
        })

        if (response.ok) {
            showToast('User unbanned successfully', 'success')
            loadBanned()
        } else {
            showToast('Failed to unban user', 'error')
        }
    } catch (error) {
        console.error('Error unbanning user:', error)
        showToast('Error unbanning user', 'error')
    }
}
</script>

<template>
    <div id="user-bans">
        <div class="pixel-card">
            <div class="header-row">
                <h1>User Bans</h1>
                <button class="ban-btn" @click="openBanModal">Ban User</button>
            </div>

            <div class="stat-row">
                <div class="stat-item">
                    <span class="label">Total Bans</span>
                    <span class="value">{{ totalBans }}</span>
                </div>
                <div class="stat-item">
                    <span class="label">Permanent</span>
                    <span class="value">{{ permanentBans }}</span>
                </div>
                <div class="stat-item">
                    <span class="label">Temporary</span>
                    <span class="value">{{ temporaryBans }}</span>
                </div>
            </div>
        </div>

        <div class="pixel-card search-card">
            <h2 class="card-title">Search Banned Users</h2>
            <div class="search-controls">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by Visitor ID, IP Address, Reason, or Username..."
                    class="search-input"
                />
                <button
                    v-if="searchQuery"
                    class="clear-btn"
                    @click="searchQuery = ''"
                >
                    Clear
                </button>
            </div>
        </div>

        <div class="pixel-card table-card">
            <div v-if="loading" class="loading">
                <p>Loading...</p>
            </div>

            <div v-else-if="filteredBanned.length" class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User / Visitor</th>
                            <th>IP Address</th>
                            <th>Reason</th>
                            <th>Type</th>
                            <th>Banned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ban in filteredBanned" :key="ban.id">
                            <td class="id">
                                <template v-if="ban.user_id && ban.user">
                                    <span class="badge-user">{{ ban.user.username }}</span>
                                </template>
                                <template v-else>
                                    {{ ban.visitor_id || '-' }}
                                </template>
                            </td>
                            <td class="ip-address">{{ ban.ip_address || '-' }}</td>
                            <td class="reason">{{ ban.reason }}</td>
                            <td class="type">
                                <span :class="ban.is_permanent ? 'badge-permanent' : 'badge-temporary'">
                                    {{ ban.is_permanent ? 'Permanent' : 'Temporary' }}
                                </span>
                            </td>
                            <td class="date">{{ new Date(ban.created_at).toLocaleString() }}</td>
                            <td class="actions">
                                <button class="unban-btn" @click="unbanUser(ban.id)" title="Unban User">
                                    Unban
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else-if="searchQuery" class="empty-state">
                <p>No banned users match your search.</p>
            </div>
            <div v-else class="empty-state">
                <p>No banned users found.</p>
            </div>
        </div>

        <UserBansModal
            :open="showBanModal"
            @close="closeBanModal"
            @banned="onBanned"
        />
    </div>
</template>

<style scoped>
#user-bans {
    animation: fadeIn 0.3s ease-out;
    margin: 0 auto;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.pixel-card {
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 20px;
    color: white;
    margin-bottom: 20px;
}

.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

h1 {
    margin: 0;
    color: #e0e0e0;
}

.ban-btn {
    background-color: #ff4d4d;
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
}

.ban-btn:hover {
    background-color: #ff3333;
}

.stat-row {
    display: flex;
    gap: 30px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.label {
    font-size: 0.7rem;
    color: #888;
    margin-bottom: 5px;
    letter-spacing: 1px;
}

.value {
    font-size: 1.5rem;
    font-weight: bold;
}

.search-card {
    margin-bottom: 20px;
    overflow: hidden;
    box-sizing: border-box;
}

.card-title {
    margin: 0 0 16px 0;
    color: #e0e0e0;
    font-size: 1.1rem;
    font-weight: 600;
}

.search-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    width: 100%;
    min-width: 0;
}

.search-input {
    flex: 1;
    min-width: 0;
    padding: 10px 15px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

.search-input:focus {
    outline: none;
    border-color: #ff4d4d;
}

.clear-btn {
    padding: 10px 15px;
    background: #333;
    border: 1px solid #555;
    border-radius: 4px;
    color: #ddd;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.clear-btn:hover {
    background: #444;
}

.loading,
.empty-state {
    padding: 40px;
    text-align: center;
    color: #888;
}

.table-container {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    border-collapse: separate;
    background: #1e1e1e;
    border-radius: 8px;
    overflow: hidden;
    min-width: 700px;
}

thead {
    background: #252525;
}

th {
    padding: 14px 16px;
    text-align: left;
    font-size: 14px;
    font-weight: bold;
    color: white;
}

td {
    padding: 14px 16px;
    font-size: 12px;
    color: white;
    vertical-align: middle;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

.badge-permanent {
    display: inline-block;
    padding: 4px 8px;
    background: rgba(255, 77, 77, 0.2);
    border: 1px solid #ff4d4d;
    color: #ff4d4d;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.badge-temporary {
    display: inline-block;
    padding: 4px 8px;
    background: rgba(255, 165, 0, 0.2);
    border: 1px solid #ffa500;
    color: #ffa500;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.badge-user {
    display: inline-block;
    padding: 4px 8px;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid #6366f1;
    color: #6366f1;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.actions {
    white-space: nowrap;
}

.unban-btn {
    padding: 6px 12px;
    background-color: #22c55e;
    border: none;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    transition: background-color 0.2s;
}

.unban-btn:hover {
    background-color: #16a34a;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    #user-bans {
        width: 100%;
    }

    .pixel-card {
        padding: 16px;
        margin-bottom: 16px;
    }

    .header-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .ban-btn {
        width: 100%;
    }

    .stat-row {
        gap: 20px;
    }

    .stat-item {
        flex: 1;
        min-width: 100px;
    }

    .search-card {
        padding: 16px;
        box-sizing: border-box;
    }

    .card-title {
        font-size: 1rem;
        margin-bottom: 12px;
    }

    .search-controls {
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }

    .search-input {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        font-size: 16px; /* Prevents zoom on iOS */
    }

    .clear-btn {
        width: 100%;
        box-sizing: border-box;
    }

    .table-card {
        padding: 12px;
        overflow-x: auto;
    }

    .unban-btn {
        padding: 4px 8px;
        font-size: 11px;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        min-width: 600px;
    }

    th, td {
        padding: 10px 12px;
        font-size: 11px;
    }
}

@media (max-width: 480px) {
    .pixel-card {
        padding: 12px;
        margin-bottom: 12px;
    }

    h1 {
        font-size: 1.3rem;
    }

    .stat-row {
        gap: 12px;
    }

    .stat-item {
        min-width: 80px;
    }

    .value {
        font-size: 1.2rem;
    }

    .label {
        font-size: 0.65rem;
    }

    .search-card {
        padding: 12px;
        box-sizing: border-box;
    }

    .card-title {
        font-size: 0.9rem;
    }

    .search-input {
        padding: 8px 12px;
        font-size: 16px; /* Prevents zoom on iOS */
    }

    .table-card {
        padding: 10px;
    }

    table {
        min-width: 500px;
    }

    th, td {
        padding: 8px 10px;
        font-size: 10px;
    }

    .unban-btn {
        padding: 4px 8px;
        font-size: 10px;
    }
}
</style>
