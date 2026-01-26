<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const metrics = ref(null)
const loading = ref(true)

const CACHE_KEY = 'lightsail_metrics'
const CACHE_TTL = 60000
const UPDATE_INTERVAL = 60000 // 1 minute

let metricsInterval = null

const formatBytes = bytes => {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return `${(bytes / 1024 ** i).toFixed(2)} ${units[i]}`
}

const getCache = () => {
    try {
        const data = localStorage.getItem(CACHE_KEY)
        const time = Number(localStorage.getItem(`${CACHE_KEY}_time`))

        if (!data || !time) return null
        if (Date.now() - time > CACHE_TTL) return null

        return JSON.parse(data) // Parse the stored JSON string
    } catch {
        localStorage.removeItem(CACHE_KEY)
        localStorage.removeItem(`${CACHE_KEY}_time`)
        return null
    }
}

const setCache = data => {
    localStorage.setItem(CACHE_KEY, JSON.stringify(data)) // Store the metrics object
    localStorage.setItem(`${CACHE_KEY}_time`, Date.now())
}

async function fetchMetrics() {
    const cached = getCache()
    if (cached) {
        metrics.value = cached
        loading.value = false
        return
    }

    try {
        const metricsData = await fetch('/server/metrics')
        if (!metricsData.ok) {
            loading.value = false
            return
        }

        const { data } = await metricsData.json()
        metrics.value = data
        setCache(data)
    } catch (error) {
        console.error('Error fetching metrics:', error)
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    loading.value = true
    await fetchMetrics()
    
    // Set up interval to update metrics every minute
    metricsInterval = setInterval(() => {
        fetchMetrics()
    }, UPDATE_INTERVAL)
})

onUnmounted(() => {
    if (metricsInterval) {
        clearInterval(metricsInterval)
        metricsInterval = null
    }
})
</script>

<template>
    <div id="dashboard">
        <div class="pixel-card">
            <h1>Admin Dashboard</h1>
            <p>Welcome to the admin dashboard. Here you can monitor site and statistics.</p>

            <div class="stat-row">
                <div class="stat-item">
                    <span class="label">ACTIVE_USERS</span>
                    <span class="value">1,024</span>
                </div>
            </div>
        </div>
        <div v-if="metrics" class="metrics-grid">

            <div class="pixel-card metric-card">
                <h3>CPU Load</h3>
                <div class="stat-row">
                    <div class="stat-item">
                        <span class="label">CURRENT</span>
                        <span class="value">{{ metrics.cpu.current }}%</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">AVG / MAX</span>
                        <span class="sub-value">Avg: {{ metrics.cpu.average }}%</span>
                        <span class="sub-value">Max: {{ metrics.cpu.maximum  }}%</span>
                    </div>
                </div>
            </div>

            <div class="pixel-card metric-card">
                <h3>Network In</h3>
                <div class="stat-row">
                    <div class="stat-item">
                        <span class="label">CURRENT</span>
                        <span class="value">{{ formatBytes(metrics.network_in.current) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">PEAK</span>
                        <span class="value">{{ formatBytes(metrics.network_in.maximum) }}</span>
                    </div>
                </div>
            </div>

            <div class="pixel-card metric-card">
                <h3>Network Out</h3>
                <div class="stat-row">
                    <div class="stat-item">
                        <span class="label">CURRENT</span>
                        <span class="value">{{ formatBytes(metrics.network_out.current) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">PEAK</span>
                        <span class="value">{{ formatBytes(metrics.network_out.maximum) }}</span>
                    </div>
                </div>
            </div>

        </div>

        <div v-else class="pixel-card">
            <p>Loading aws metrics...</p>
        </div>
    </div>
</template>

<style scoped>
#dashboard {
    animation: fadeIn 0.3s ease-out;
    margin: 0 auto;
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
}

@media (max-width: 768px) {
    #dashboard {
        width: 100%;
    }

    .pixel-card {
        padding: 16px;
        margin-bottom: 16px;
    }

    .metrics-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .stat-row {
        flex-direction: column;
        gap: 15px;
    }

    .stat-item {
        width: 100%;
    }

    h1 {
        font-size: 1.5rem;
    }

    h3 {
        font-size: 1.1rem;
    }

    .value {
        font-size: 1.3rem;
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

    h3 {
        font-size: 1rem;
    }

    .value {
        font-size: 1.2rem;
    }

    .label {
        font-size: 0.65rem;
    }
}

.metric-card {
    margin-bottom: 0; /* Override default margin for grid items */
}

h1, h3 {
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
</style>
