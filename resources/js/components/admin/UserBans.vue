<script setup>
import {onMounted, ref, computed, watch, onUnmounted} from 'vue'

const visitors = ref(null)
const banned = ref(null)
const loading = ref(true)
const showBanPicker = ref(false)
const showBanForm = ref(false)
const selectedVisitor = ref(null)

const searchQuery = ref('')
const riskFilter = ref('all') // 'all', 'high', 'low', 'none'

const banForm = ref({
    reason: '',
    hide_pixels: false,
    is_permanent: true,
    banned_until: ''
})

const banMetrics = ref({
    current: 75,
    permanent: 2,
    temporary: 4,
    latest_banned_user: {
        id: 1,
        username: 'banned_user_1',
        reason: 'Spamming',
        banned_at: '2024-01-15T10:30:00Z',
        banned_by: 'admin_user',
    }
})

const filteredVisitors = computed(() => {
    if (!visitors.value) return []

    let filtered = visitors.value

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(v =>
            v.visitor_id.toLowerCase().includes(query) ||
            v.ip_address.toLowerCase().includes(query)
        )
    }

    if (riskFilter.value !== 'all') {
        const riskMap = {
            'high': (v) => v.risk_score >= 2,
            'low': (v) => v.risk_score === 1,
            'none': (v) => v.risk_score === 0
        }
        filtered = filtered.filter(riskMap[riskFilter.value])
    }

    return filtered
})

onMounted(async () => {
    try {
        const [visitorsRes, bannedRes] = await Promise.all([
            fetch('/visitors'),
            fetch('/banned-visitors')
        ])

        if (!visitorsRes.ok || !bannedRes.ok) {
            throw new Error('Failed to fetch metrics')
        }

        visitors.value = await visitorsRes.json()
        banned.value = await bannedRes.json()
    } catch (error) {
        console.error('Error loading data:', error)
    } finally {
        loading.value = false
    }
})

// Watch for picker/form visibility to prevent body scroll
watch([showBanPicker, showBanForm], ([picker, form]) => {
    if (picker || form) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

onUnmounted(() => {
    document.body.style.overflow = ''
})

// Open ban form for specific user
const openBanForm = (visitor) => {
    selectedVisitor.value = visitor
    showBanForm.value = true

    banForm.value = {
        reason: '',
        hide_pixels: false,
        is_permanent: true,
        banned_until: ''
    }
}

const closeBanForm = () => {
    showBanForm.value = false
    selectedVisitor.value = null
}

const submitBan = async () => {
    if (!selectedVisitor.value) return

    if (!banForm.value.reason.trim()) {
        alert('Please provide a reason for the ban')
        return
    }

    if (!banForm.value.is_permanent && !banForm.value.banned_until) {
        alert('Please select an end date for temporary ban')
        return
    }

    const formData = new FormData();
    formData.append('visitor_id', selectedVisitor.value.visitor_id);
    formData.append('ip_address', selectedVisitor.value.ip_address);
    formData.append('reason', banForm.value.reason);
    formData.append('hide_pixels', banForm.value.hide_pixels ? '1' : '0');
    formData.append('is_permanent', banForm.value.is_permanent ? '1' : '0');

    if (!banForm.value.is_permanent && banForm.value.banned_until) {
        formData.append('banned_until', banForm.value.banned_until);
    }

    try {
        const response = await fetch('/ban-visitors', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        })

        if (!response.ok) {
            throw new Error('Failed to ban user')
        }

        alert('User banned successfully!')

        // Refresh data
        const [visitorsRes, bannedRes] = await Promise.all([
            fetch('/visitors'),
            fetch('/banned-visitors')
        ])

        visitors.value = await visitorsRes.json()
        banned.value = await bannedRes.json()

        // Close everything
        closeBanForm()
        showBanPicker.value = false

    } catch (error) {
        console.error('Error banning user:', error)
        alert('Failed to ban user. Please try again.')
    }
}

// Clear filters
const clearFilters = () => {
    searchQuery.value = ''
    riskFilter.value = 'all'
}
</script>

<template>
    <div id="user-bans">
        <div class="pixel-card">
            <h1>User Bans</h1>
            <div class="stat-row">
                <div class="stat-item">
                    <span class="label">Total Bans</span>
                    <span class="value">1,024</span>
                </div>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="pixel-card metric-card">
                <h3>Banned Users</h3>
                <div class="stat-row">
                    <div class="stat-item">
                        <span class="label">CURRENT</span>
                        <span class="value">{{ banMetrics.current }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">TEMP / PERMA</span>
                        <span class="sub-value">Temp: {{ banMetrics.temporary }}</span>
                        <span class="sub-value">Perma: {{ banMetrics.permanent }}</span>
                    </div>
                </div>
            </div>

            <div class="pixel-card metric-card">
                <h3>Latest Ban</h3>
                <div class="stat-row">
                    <div class="stat-item">
                        <span class="label">who</span>
                        <span class="value"></span>
                    </div>
                    <div class="stat-item">
                        <span class="label">time</span>
                        <span class="value"></span>
                    </div>
                </div>
            </div>

            <div class="pixel-card metric-card">
                <h3>Next Unban</h3>
                <div class="stat-row">
                    <div class="stat-item">
                        <span class="label">CURRENT</span>
                        <span class="value"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="visitors-table">
            <div id="table-header">
                <h1>Banned Users</h1>
                <button
                    @click="showBanPicker = !showBanPicker"
                    :class="{ 'cancel-btn': showBanPicker }"
                >
                    {{ showBanPicker ? 'Cancel Selection' : 'Ban New User' }}
                </button>
            </div>

            <div v-if="loading" class="pixel-card">
                <p>Loading visitors...</p>
            </div>

            <table v-else-if="Array.isArray(banned) && banned.length">
                <thead>
                <tr>
                    <th>visitor id</th>
                    <th>ip address</th>
                    <th>reason</th>
                    <th>type</th>
                    <th>banned at</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="ban in banned" :key="ban.visitor_id">
                    <td class="id">{{ ban.visitor_id }}</td>
                    <td class="ip-address">{{ ban.ip_address }}</td>
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
            <div v-else class="pixel-card">
                <p>No banned users found.</p>
            </div>
        </div>

        <!-- Ban Picker Modal -->
        <div v-if="showBanPicker" class="modal-overlay" @click.self="showBanPicker = false">
            <div class="modal-content picker-section">
                <div class="picker-header">
                    <h1>Select User to Ban</h1>
                    <button class="close-btn" @click="showBanPicker = false">✕</button>
                </div>

                <!-- Search and Filter Controls -->
                <div class="filter-controls">
                    <div class="search-box">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by Visitor ID or IP Address..."
                            class="search-input"
                        />
                    </div>

                    <div class="filter-group">
                        <label>Risk Level:</label>
                        <select v-model="riskFilter" class="filter-select">
                            <option value="all">All Levels</option>
                            <option value="high">High Risk (2+)</option>
                            <option value="low">Low Risk (1)</option>
                            <option value="none">No Risk (0)</option>
                        </select>
                    </div>

                    <button
                        v-if="searchQuery || riskFilter !== 'all'"
                        @click="clearFilters"
                        class="clear-btn"
                    >
                        Clear Filters
                    </button>
                </div>

                <div class="results-info">
                    Showing {{ filteredVisitors.length }} of {{ visitors?.length || 0 }} visitors
                </div>

                <div class="table-wrapper">
                    <table v-if="filteredVisitors && filteredVisitors.length > 0">
                        <thead>
                        <tr>
                            <th>visitor id</th>
                            <th>ip address</th>
                            <th>risk score</th>
                            <th>created at</th>
                            <th>action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            :class="{
                                'high-risk': visitor.risk_score >= 2,
                                'low-risk': visitor.risk_score === 1,
                                'no-risk': visitor.risk_score === 0
                            }"
                            v-for="visitor in filteredVisitors"
                            :key="visitor.visitor_id"
                        >
                            <td class="id">{{ visitor.visitor_id }}</td>
                            <td class="ip-address">{{ visitor.ip_address }}</td>
                            <td class="risk-score">{{ visitor.risk_score ?? 0 }}</td>
                            <td class="date">{{ new Date(visitor.created_at).toLocaleString() }}</td>
                            <td>
                                <button class="ban-action-btn" @click="openBanForm(visitor)">
                                    BAN
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div v-else class="no-results">
                        <p>No visitors match your filters</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ban Form Modal -->
        <div v-if="showBanForm" class="modal-overlay" @click.self="closeBanForm">
            <div class="modal-content ban-form">
                <div class="form-header">
                    <h2>Ban User</h2>
                    <button class="close-btn" @click="closeBanForm">✕</button>
                </div>

                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label">Visitor ID:</span>
                        <span class="info-value">{{ selectedVisitor?.visitor_id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">IP Address:</span>
                        <span class="info-value">{{ selectedVisitor?.ip_address }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Risk Score:</span>
                        <span class="info-value risk-badge" :class="{
                            'high': selectedVisitor?.risk_score >= 2,
                            'low': selectedVisitor?.risk_score === 1
                        }">
                            {{ selectedVisitor?.risk_score ?? 0 }}
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitBan" class="ban-form-fields">
                    <div class="form-group">
                        <label for="reason">Reason for Ban *</label>
                        <textarea
                            id="reason"
                            v-model="banForm.reason"
                            placeholder="Enter the reason for banning this user..."
                            rows="4"
                            required
                        ></textarea>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" v-model="banForm.hide_pixels" />
                            <span>Hide user's pixels</span>
                        </label>
                        <small>Remove all pixels placed by this user from the canvas</small>
                    </div>

                    <div class="form-group">
                        <label>Ban Duration</label>
                        <div class="radio-group">
                            <label>
                                <input
                                    type="radio"
                                    :value="true"
                                    v-model="banForm.is_permanent"
                                />
                                <span>Permanent Ban</span>
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    :value="false"
                                    v-model="banForm.is_permanent"
                                />
                                <span>Temporary Ban</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="!banForm.is_permanent" class="form-group">
                        <label for="banned_until">Ban Until *</label>
                        <input
                            id="banned_until"
                            type="datetime-local"
                            v-model="banForm.banned_until"
                            :required="!banForm.is_permanent"
                        />
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="closeBanForm" class="btn-cancel">
                            Cancel
                        </button>
                        <button type="submit" class="btn-submit">
                            Confirm Ban
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
#user-bans {
    animation: fadeIn 0.3s ease-out;
    margin: 0 auto;
}

/* Scrollbar Styling */
#user-bans ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

#user-bans ::-webkit-scrollbar-track {
    background: #1e1e1e;
    border-radius: 5px;
}

#user-bans ::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 5px;
    border: 2px solid #1e1e1e;
}

#user-bans ::-webkit-scrollbar-thumb:hover {
    background: #555;
}

#user-bans * {
    scrollbar-width: thin;
    scrollbar-color: #444 #1e1e1e;
}

.pixel-card {
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 20px;
    color: white;
    margin-bottom: 20px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.metric-card {
    margin-bottom: 0;
}

h1, h2, h3 {
    margin-top: 0;
    color: #e0e0e0;
}

.stat-row {
    display: flex;
    justify-content: space-between;
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

.sub-value {
    font-size: 0.9rem;
    color: #bbb;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.visitors-table {
    margin-bottom: 30px;
}

.visitors-table h1 {
    font-weight: bold;
    color: black;
}

#table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

#table-header button {
    background-color: #ff4d4d;
    border: none;
    color: white;
    padding: 10px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
}

#table-header button.cancel-btn {
    background-color: #555;
    color: #ddd;
}

#table-header button:hover {
    filter: brightness(1.1);
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    animation: fadeIn 0.2s ease-out;
}

.modal-content {
    background: #1e1e1e;
    border: 2px solid #444;
    border-radius: 8px;
    padding: 25px;
    max-width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.7);
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.picker-section {
    width: 1000px;
}

.picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #333;
}

.picker-header h1 {
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
}

.close-btn:hover {
    background: #333;
    color: #fff;
}

/* Filter Controls */
.filter-controls {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-input {
    width: 100%;
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

.search-input::placeholder {
    color: #666;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    color: #888;
    font-size: 14px;
}

.filter-select {
    padding: 10px 15px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    cursor: pointer;
}

.filter-select:focus {
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
    border-color: #666;
}

.results-info {
    color: #888;
    font-size: 13px;
    margin-bottom: 15px;
}

.table-wrapper {
    max-height: 500px;
    overflow-y: auto;
}

.no-results {
    padding: 40px;
    text-align: center;
    color: #666;
}

.ban-action-btn {
    background-color: rgba(255, 77, 77, 0.2);
    border: 1px solid #ff4d4d;
    color: #ff4d4d;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.ban-action-btn:hover {
    background-color: #ff4d4d;
    color: white;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: separate;
    background: #1e1e1e;
    border-radius: 8px;
    overflow: hidden;
}

thead {
    background: #252525;
    position: sticky;
    top: 0;
    z-index: 2;
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
    font-weight: bold;
    color: white;
    vertical-align: middle;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
    filter: brightness(1.1);
}

.high-risk {
    background-color: rgba(104, 35, 35, 0.4) !important;
    border-left: 3px solid #ff4d4d;
}

.low-risk {
    background-color: rgba(188, 77, 30, 0.2) !important;
    border-left: 3px solid #bc4d1e;
}

.no-risk {
    background: transparent;
    border-left: 3px solid transparent;
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

/* Ban Form Styles */
.ban-form {
    width: 600px;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #333;
}

.form-header h2 {
    margin: 0;
    color: #e0e0e0;
}

.user-info {
    background: #252525;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-label {
    color: #888;
    font-size: 13px;
}

.info-value {
    color: #e0e0e0;
    font-weight: bold;
}

.risk-badge {
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
}

.risk-badge.high {
    background: rgba(255, 77, 77, 0.3);
    color: #ff4d4d;
}

.risk-badge.low {
    background: rgba(255, 165, 0, 0.3);
    color: #ffa500;
}

.ban-form-fields {
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
    font-family: inherit;
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

.checkbox-group small {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
    display: block;
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

.btn-cancel,
.btn-submit {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel {
    background: #333;
    color: #ddd;
}

.btn-cancel:hover {
    background: #444;
}

.btn-submit {
    background: #ff4d4d;
    color: white;
}

.btn-submit:hover {
    background: #ff3333;
}
</style>
