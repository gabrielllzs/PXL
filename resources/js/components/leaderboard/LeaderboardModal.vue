<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const activeTab = ref('player')
const loading = ref(false)

const leaderboards = ref({
    country: [],
    player: [],
    guild: []
})

const headersMap = {
    country: { col1: 'Country', col2: 'Pixels' },
    player: { col1: 'Player', col2: 'Pixels' },
    guild: { col1: 'Guild', col2: 'Pixels' }
}

const currentLeaderboard = computed(() => leaderboards.value[activeTab.value])
const tableHeaders = computed(() => headersMap[activeTab.value])

async function fetchLeaderboard(tab) {
    loading.value = true

    try {
        if (tab === 'player') {
            const { data } = await axios.get('/leaderboard/users')
            leaderboards.value.player = data
                .slice(0, 15)
                .map((user, i) => ({
                    rank: i + 1,
                    name: user.username,
                    pixels: user.pixels_placed || 0
                }))
        }

        if (tab === 'guild') {
            const { data } = await axios.get('/leaderboard/groups')
            leaderboards.value.guild = data
                .slice(0, 15)
                .map((group, i) => ({
                    rank: i + 1,
                    name: group.name,
                    pixels: group.pixels || 0
                }))
        }

        if (tab === 'country') {
            const { data } = await axios.get('/leaderboard/countries')
            leaderboards.value.country = data
                .slice(0, 15)
                .map((country, i) => ({
                    rank: i + 1,
                    name: country.country,
                    pixels: country.pixels || 0
                }))
        }
    } catch {
        leaderboards.value[tab] = []
    } finally {
        loading.value = false
    }
}

watch(
    () => [props.modelValue, activeTab.value],
    ([open]) => {
        if (open) fetchLeaderboard(activeTab.value)
    }
)

function close() {
    emit('update:modelValue', false)
}

function setTab(tab) {
    activeTab.value = tab
}
</script>


<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h2>Leaderboard</h2>
                <button class="modal-close" @click="close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="tabs">
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'country' }"
                    @click="setTab('country')"
                >
                    Country
                </button>
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'player' }"
                    @click="setTab('player')"
                >
                    Player
                </button>
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'guild' }"
                    @click="setTab('guild')"
                >
                    Guild
                </button>
            </div>
            <div class="leaderboard-table">
                <div class="table-header">
                    <div class="col-rank">Rank</div>
                    <div class="col-name">{{ tableHeaders.col1 }}</div>
                    <div class="col-pixels">{{ tableHeaders.col2 }}</div>
                </div>
                <div v-if="loading" class="loading">
                    Loading...
                </div>
                <div v-else class="table-body">
                    <div
                        v-for="entry in currentLeaderboard"
                        :key="entry.rank"
                        class="table-row"
                        :class="{ 'top-three': entry.rank <= 3 }"
                    >
                        <div class="col-rank">
                            <span class="rank-number">{{ entry.rank }}</span>
                        </div>
                        <div class="col-name">{{ entry.name }}</div>
                        <div class="col-pixels">{{ entry.pixels.toLocaleString() }}</div>
                    </div>
                    <div v-if="currentLeaderboard.length === 0" class="empty-state">
                        No data available
                    </div>
                </div>
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
    border-radius: 16px;
    padding: 0;
    max-width: 900px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 24px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: #1e1e1e;
    font-size: 28px;
    font-weight: 700;
}

.modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    transition: all 0.2s;
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

.tabs {
    display: flex;
    gap: 8px;
    padding: 0 24px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
    background: #f9f9f9;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    background: transparent;
    color: #666;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: #2563eb;
    background: rgba(37, 99, 235, 0.05);
}

.tab-btn.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
    font-weight: 600;
}

.leaderboard-table {
    overflow-y: auto;
    flex: 1;
}

.table-header {
    display: grid;
    grid-template-columns: 80px 1fr 150px;
    gap: 16px;
    padding: 16px 24px;
    background: #f9f9f9;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
    font-weight: 600;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    z-index: 2;
}

.table-body {
    display: flex;
    flex-direction: column;
}

.table-row {
    display: grid;
    grid-template-columns: 80px 1fr 150px;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    align-items: center;
    transition: background 0.2s;
}

.table-row:hover {
    background: rgba(132, 176, 245, 0.05);
}

.table-row.top-three {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 215, 0, 0.05) 100%);
}

.table-row.top-three:hover {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 215, 0, 0.1) 100%);
}

.col-rank {
    display: flex;
    align-items: center;
}

.rank-number {
    font-weight: 700;
    font-size: 16px;
    color: #1e1e1e;
}

.table-row.top-three .rank-number {
    color: #d4af37;
    font-size: 18px;
}

.col-name {
    font-weight: 600;
    color: #1e1e1e;
    font-size: 15px;
}

.col-pixels {
    font-weight: 600;
    color: #2563eb;
    text-align: right;
    font-size: 15px;
}

.loading {
    padding: 40px;
    text-align: center;
    color: #666;
}

.empty-state {
    padding: 40px;
    text-align: center;
    color: #999;
}

@media (max-width: 768px) {
    .table-header,
    .table-row {
        grid-template-columns: 60px 1fr 100px;
        gap: 8px;
        padding: 12px 16px;
    }

    .tabs {
        padding: 0 16px;
    }

    .tab-btn {
        padding: 10px 16px;
        font-size: 14px;
    }

    .modal-content {
        width: 95%;
        max-height: 95vh;
    }
}
</style>
