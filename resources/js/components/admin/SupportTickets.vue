<script setup>

</script>
<template>
    <div id="dashboard">
        <div class="pixel-card">
            <h1>Support Tickets</h1>

            <div class="stat-row">
                <div class="stat-item">
                    <span class="label">TOTAL TICKETS</span>
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
                        <span class="value">5</span>
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

