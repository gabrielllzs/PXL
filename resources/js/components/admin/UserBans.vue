<script setup>
import { ref, computed, onMounted } from 'vue'
import UserBansModal from './UserBansModal.vue'

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

        <div class="pixel-card">
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

            <div v-if="loading" class="loading">
                <p>Loading...</p>
            </div>

            <table v-else-if="filteredBanned.length">
                <thead>
                    <tr>
                        <th>User / Visitor</th>
                        <th>IP Address</th>
                        <th>Reason</th>
                        <th>Type</th>
                        <th>Banned At</th>
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
                    </tr>
                </tbody>
            </table>

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

.search-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
}

.search-input {
    flex: 1;
    padding: 10px 15px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    color: white;
    font-size: 14px;
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

table {
    width: 100%;
    border-collapse: separate;
    background: #1e1e1e;
    border-radius: 8px;
    overflow: hidden;
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
</style>
