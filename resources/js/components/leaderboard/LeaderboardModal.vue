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
    guild: { col1: 'Group', col2: 'Pixels' }
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
                    pixels: user.pixels_placed || 0,
                    level: user.level || 1
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
                    pixels: country.pixels || 0,
                    emoji: country.emoji || ''
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
                <div class="header-title">
                    <div class="header-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22,7H16.333V4a1,1,0,0,0-1-1H8.667a1,1,0,0,0-1,1v7H2a1,1,0,0,0-1,1v8a1,1,0,0,0,1,1H22a1,1,0,0,0,1-1V8A1,1,0,0,0,22,7ZM7.667,19H3V13H7.667Zm6.666,0H9.667V5h4.666ZM21,19H16.333V9H21Z"/>
                        </svg>
                    </div>
                    <h2>Leaderboard</h2>
                </div>
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="tab-icon">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM6.262 6.072a8.25 8.25 0 1010.562-.766 4.5 4.5 0 01-1.318 1.357L14.25 7.5l.165.33a.809.809 0 01-1.086 1.085l-.604-.302a1.125 1.125 0 00-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 01-2.288 4.04l-.723.724a1.125 1.125 0 01-1.298.21l-.153-.076a1.125 1.125 0 01-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 01-.21-1.298L9.75 12l-1.64-1.64a6 6 0 01-1.676-3.257l-.172-1.03z" clip-rule="evenodd"/>
                    </svg>
                    Country
                </button>
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'player' }"
                    @click="setTab('player')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="tab-icon">
                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                    </svg>
                    Player
                </button>
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'guild' }"
                    @click="setTab('guild')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="tab-icon">
                        <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z"/>
                    </svg>
                    Group
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
                        :class="{
                            'top-three': entry.rank <= 3,
                            'rank-1': entry.rank === 1,
                            'rank-2': entry.rank === 2,
                            'rank-3': entry.rank === 3
                        }"
                    >
                        <div class="col-rank">
                            <span class="rank-number">{{ entry.rank }}</span>
                        </div>
                        <div class="col-name country-cell">
                            <span v-if="activeTab === 'country'" class="country-emoji">{{ entry.emoji || '' }}</span>
                            <span class="country-name">
                                {{ entry.name }}
                                <span v-if="activeTab === 'player' && entry.level" class="level-badge">Lv.{{ entry.level }}</span>
                            </span>
                        </div>
                        <div class="col-pixels">{{ entry.pixels }}</div>
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
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    pointer-events: auto;
}

.modal-content {
    background: #fafafa;
    border-radius: 20px;
    padding: 0;
    max-width: 900px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    font-family: 'pixel art', monospace;
}

.modal-header {
    padding: 20px 24px;
    background: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.header-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
}

.header-icon svg {
    width: 24px;
    height: 24px;
}

.modal-header h2 {
    margin: 0;
    color: #1e1e1e;
    font-size: 24px;
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
    color: #888;
    transition: all 0.2s;
    border-radius: 10px;
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
    gap: 4px;
    padding: 12px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    background: white;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border: none;
    background: transparent;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border-radius: 10px;
    transition: all 0.2s;
}

.tab-icon {
    width: 18px;
    height: 18px;
}

.tab-btn:hover {
    color: #1e1e1e;
    background: rgba(0, 0, 0, 0.04);
}

.tab-btn.active {
    color: #2563eb;
    background: rgba(37, 99, 235, 0.1);
    font-weight: 600;
}

.leaderboard-table {
    overflow-y: auto;
    flex: 1;
    min-height: 700px;
    max-height: 700px;
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

.table-row.rank-1 {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 215, 0, 0.08) 100%);
}

.table-row.rank-1:hover {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 215, 0, 0.12) 100%);
}

.table-row.rank-2 {
    background: linear-gradient(90deg, rgba(192, 192, 192, 0.15) 0%, rgba(192, 192, 192, 0.08) 100%);
}

.table-row.rank-2:hover {
    background: linear-gradient(90deg, rgba(192, 192, 192, 0.2) 0%, rgba(192, 192, 192, 0.12) 100%);
}

.table-row.rank-3 {
    background: linear-gradient(90deg, rgba(205, 127, 50, 0.15) 0%, rgba(205, 127, 50, 0.08) 100%);
}

.table-row.rank-3:hover {
    background: linear-gradient(90deg, rgba(205, 127, 50, 0.2) 0%, rgba(205, 127, 50, 0.12) 100%);
}

.col-rank {
    display: flex;
    justify-content: center;
    align-items: center;
}

.rank-number {
    font-weight: 700;
    font-size: 16px;
    color: #1e1e1e;
}

.table-row.top-three .rank-number {
    font-size: 18px;
}

.table-row.rank-1 .rank-number {
    color: #d4af37;
}

.table-row.rank-2 .rank-number {
    color: #c0c0c0;
}

.table-row.rank-3 .rank-number {
    color: #cd7f32;
}

.col-name {
    font-weight: 600;
    color: #1e1e1e;
    font-size: 15px;
}

.country-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.country-emoji {
    font-size: 1.4em;
    line-height: 1;
    min-width: 1.4em;
    text-align: center;
}

.country-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 8px;
}

.level-badge {
    background: #2563eb;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
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
        width: 100%;
        height: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .leaderboard-table {
        min-height: unset;
        max-height: unset;
        flex: 1;
    }

    .modal-overlay {
        padding: 0;
    }
}
</style>
