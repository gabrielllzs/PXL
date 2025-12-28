<script setup>
import { ref, onMounted } from 'vue'

const banMetrics = ref({
    current: 75,
    permanent: 2,
    temporary: 4,
    // Note: If you want a list of users, this should probably be an array separate from the stats
    latest_banned_user: {
        id : 1,
        username: 'banned_user_1',
        reason: 'Spamming',
        banned_at: '2024-01-15T10:30:00Z',
        banned_by: 'admin_user',
    }
});

</script>

<template>
    <div id="userbans">
        <div class="pixel-card">
            <h1>user bans</h1>

            <div class="stat-row">
                <div class="stat-item">
                    <span class="label">Users Banned</span>
                    <span class="value">1,024</span>
                </div>
            </div>
        </div>

        <div v-if="banMetrics" class="metrics-grid">

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
                        <span class="sub-value">Perma: {{ banMetrics.permanent  }}</span>
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
                    <div class="stat-item">
                        <span class="label">reason</span>
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
                    <div class="stat-item">
                        <span class="label">PEAK</span>
                        <span class="value"></span>
                    </div>
                </div>
            </div>

        </div>

        <div v-else class="pixel-card">
            <p>Loading info...</p>
        </div>
    </div>
</template>

<style scoped>
#userbans {
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
