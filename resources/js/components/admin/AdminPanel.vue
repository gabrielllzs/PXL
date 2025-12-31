<script setup>
import { ref, onMounted } from 'vue'
import Dashboard from './Dashboard.vue'
import UserBans from './UserBans.vue';

const user = ref(null)
const activePage = ref('dashboard')

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

onMounted(async () => {
    const response = await fetch('/api/me', { credentials: 'include' })
    if (response.ok) user.value = await response.json()

})
</script>

<template>
    <div class="container">
        <div id="side-panel">
            <div id="side-panel-logo">
                <h2>PIXEL_ADMIN</h2>
            </div>
            <div id="side-panel-content">
                <ul>
                    <li   :class="{ active: activePage === 'dashboard' }" @click="activePage = 'dashboard'">
                        <span>> Dashboard</span>
                    </li>
                    <li   :class="{ active: activePage === 'user bans' }"  @click="activePage = 'user bans'">
                        <span>> User Bans</span>
                    </li>
                    <li>
                        <span>> Support Tickets</span>
                    </li>
                    <li>
                        <span>> Activity Logs</span>
                    </li>
                    <li>
                        <form method="POST" action="/logout">
                            <input type="hidden" name="_token" :value="csrfToken" />
                            <button type="submit" class="logout-btn">
                                > Logout
                            </button>
                        </form>
                    </li>

                </ul>
            </div>
        </div>
        <div id="main-panel">
            <div id="main-panel-header">
                <h2>//{{ activePage }}</h2>
                <p v-if="user" class="user-status">
                   {{ user.name }}
                </p>
            </div>
            <div id="main-panel-content">
                <Dashboard v-if="activePage === 'dashboard'" />
                <UserBans v-if="activePage === 'user bans'" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    display: flex;
    height: 100vh;
    width: 100vw;
}

#side-panel {
    width: 260px;
    height: 100%;
    background-color: #dfdfdf;
}

#side-panel-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    height: 60px;
}

#side-panel-logo h2 {
    margin: 0;
    font-size: 24px;
}

#side-panel-content {
    padding: 20px 0;
    height: calc(100% - 60px);
    display: flex;

}

#side-panel-content ul {
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    width: 100%;
}

#side-panel-content li {
    list-style: none;
    padding: 15px 25px;
    cursor: pointer;
    transition: all 0.1s;
    font-weight: bold;
}

#side-panel-content li:hover {
    padding-left: 30px;
}

#side-panel-content li.active {
    background-color: #c0c0c0;
    border-left: 8px solid #000000;
}

#side-panel-content li:last-child {
    margin-top: auto;
}

/* Main Panel */
#main-panel {
    flex-grow: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
}

#main-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    height: 64px;
}

#main-panel-header h2 {
    font-size: 1.1rem;

}

.user-status {
    font-size: 0.9rem;
}

#main-panel-content {
    padding: 30px;
    overflow-y: auto;
    flex-grow: 1;
}

.placeholder-view {
    padding: 20px;
}
</style>
