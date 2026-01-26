<script setup>
import { ref, computed, onMounted } from 'vue'

const tickets = ref([])
const loading = ref(true)
const filter = ref('all')

const filteredTickets = computed(() => {
    if (filter.value === 'all') return tickets.value
    return tickets.value.filter(ticket => ticket.type === filter.value)
})

const bugCount = computed(() => tickets.value.filter(ticket => ticket.type === 'bug').length)
const suggestionCount = computed(() => tickets.value.filter(ticket => ticket.type === 'suggestion').length)

onMounted(async () => {
    try {
        const res = await fetch('/admin/feedback')
        if (res.ok) tickets.value = await res.json()
    } catch (e) {
        console.error('Failed to load tickets:', e)
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div id="support-tickets">
        <div class="pixel-card">
            <h1>Support Tickets</h1>
            <div class="stat-row">
                <div class="stat-item">
                    <span class="label">TOTAL</span>
                    <span class="value">{{ tickets.length }}</span>
                </div>
                <div class="stat-item">
                    <span class="label">BUGS</span>
                    <span class="value bug">{{ bugCount }}</span>
                </div>
                <div class="stat-item">
                    <span class="label">SUGGESTIONS</span>
                    <span class="value suggestion">{{ suggestionCount }}</span>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <button :class="{ active: filter === 'all' }" @click="filter = 'all'">All</button>
            <button :class="{ active: filter === 'bug' }" @click="filter = 'bug'">Bugs</button>
            <button :class="{ active: filter === 'suggestion' }" @click="filter = 'suggestion'">Suggestions</button>
        </div>

        <div v-if="loading" class="pixel-card">
            <p>Loading tickets...</p>
        </div>

        <div v-else-if="filteredTickets.length === 0" class="pixel-card">
            <p>No tickets found.</p>
        </div>

        <div v-else class="tickets-list">
            <div v-for="ticket in filteredTickets" :key="ticket.id" class="ticket-card">
                <div class="ticket-header">
                    <span class="ticket-type" :class="ticket.type">{{ ticket.type }}</span>
                    <span class="ticket-date">{{ new Date(ticket.created_at).toLocaleString() }}</span>
                </div>
                <div class="ticket-info">
                    <span class="ticket-email">{{ ticket.email }}</span>
                    <span v-if="ticket.username" class="ticket-username">{{ ticket.username }}</span>
                </div>
                <p class="ticket-message">{{ ticket.message }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
#support-tickets {
    animation: fadeIn 0.3s ease-out;
}

.pixel-card {
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 20px;
    color: white;
    margin-bottom: 20px;
}

h1 {
    margin-top: 0;
    color: #e0e0e0;
}

.stat-row {
    display: flex;
    gap: 40px;
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

.value.bug { color: #ff4d4d; }
.value.suggestion { color: #4da6ff; }

.filter-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-bar button {
    padding: 8px 16px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    color: #888;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
}

.filter-bar button:hover {
    background: #333;
    color: #fff;
}

.filter-bar button.active {
    background: #444;
    color: #fff;
    border-color: #666;
}

.tickets-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ticket-card {
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 16px;
    color: white;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.ticket-type {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.ticket-type.bug {
    background: rgba(255, 77, 77, 0.2);
    color: #ff4d4d;
}

.ticket-type.suggestion {
    background: rgba(77, 166, 255, 0.2);
    color: #4da6ff;
}

.ticket-date {
    font-size: 12px;
    color: #666;
}

.ticket-info {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13px;
}

.ticket-email {
    color: #aaa;
}

.ticket-username {
    color: #888;
}

.ticket-message {
    margin: 0;
    color: #ddd;
    line-height: 1.5;
    white-space: pre-wrap;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    #support-tickets {
        width: 100%;
    }

    .pixel-card {
        padding: 16px;
        margin-bottom: 16px;
    }

    .stat-row {
        gap: 20px;
    }

    .stat-item {
        flex: 1;
        min-width: 100px;
    }

    .filter-bar {
        flex-wrap: wrap;
    }

    .filter-bar button {
        flex: 1;
        min-width: 80px;
    }

    .ticket-card {
        padding: 12px;
    }

    .ticket-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .ticket-info {
        flex-direction: column;
        gap: 4px;
    }

    h1 {
        font-size: 1.5rem;
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

    .filter-bar button {
        padding: 6px 12px;
        font-size: 14px;
    }

    .ticket-card {
        padding: 10px;
    }

    .ticket-message {
        font-size: 13px;
    }
}
</style>

