<script setup>
import { ref, onMounted } from 'vue'

const metrics = ref(null)
const loading = ref(true)

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

onMounted(async () => {
    try {
        const response = await fetch('/server/metrics')
        if (response.ok) {
            const json = await response.json()
            if (json.success) {
                metrics.value = json.data
            }
        } else {
            console.error('Failed to fetch metrics:', response.statusText)
        }
    } catch (error) {
        console.error('Error loading data:', error)
    } finally {
        loading.value = false
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
