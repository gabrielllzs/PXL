<script setup>

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
                <div class="stat-item">
                    <span class="label">SERVER_LOAD</span>
                    <span class="value">12%</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
#dashboard {
    animation: fadeIn 0.3s ease-out;
}

.pixel-card {
    background: #2d2d2d;
    border: 4px solid #000;
    padding: 24px;
    box-shadow: 8px 8px 0px #000; /* Rigid pixel shadow */
}

h1 {
    margin-top: 0;
    color: #00ff41;
    text-shadow: 2px 2px #000;
}

p {
    line-height: 1.6;
    color: #ccc;
}

.stat-row {
    display: flex;
    gap: 20px;
    margin-top: 24px;
}

.stat-item {
    background: #1a1a1a;
    border: 2px solid #444;
    padding: 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.label {
    font-size: 0.7rem;
    color: #888;
    margin-bottom: 5px;
}

.value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
