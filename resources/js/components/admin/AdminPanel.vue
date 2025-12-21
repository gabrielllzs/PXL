<script setup>
import { ref, onMounted } from 'vue'
import Dashboard from './Dashboard.vue'

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
                <h2>PIXEL_OS</h2>
            </div>
            <div id="side-panel-content">
                <ul>
                    <li   :class="{ active: activePage === 'dashboard' }" @click="activePage = 'dashboard'">
                        <span>> Dashboard</span>
                    </li>
                    <li   :class="{ active: activePage === 'users bans' }"  @click="activePage = 'users bans'">
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
                <div v-if="activePage === 'users bans'" class="placeholder-view">
                    <h1>Banned Users List</h1>
                    <p>Manage restricted access here.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    display: flex;
    height: 100vh;
    width: 100vw;
    color: #f0f0f0;
}

#side-panel {
    width: 260px;
    height: 100%;
    background-color: #1a1a1a;
    border-right: 4px solid #000;
    box-shadow: inset -2px 0px 0px #333;
}

#side-panel-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    height: 60px;
    background-color: #000;
    color: #00ff41; /* Matrix/Pixel Green */
    border-bottom: 4px solid #333;
}

#side-panel-logo h2 {
    font-size: 1.2rem;
    letter-spacing: 3px;
    margin: 0;
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
    color: #aaa;
    border-bottom: 2px solid #000;
    transition: all 0.1s;
    font-weight: bold;
}

#side-panel-content li:hover {
    background-color: #252525;
    color: #fff;
    padding-left: 30px;
}

#side-panel-content li.active {
    background-color: #333;
    color: #00ff41;
    border-left: 8px solid #00ff41;
}

#side-panel-content li:last-child {
    margin-top: auto;
    border-top: 2px solid #000;
}

/* Main Panel */
#main-panel {
    flex-grow: 1;
    height: 100%;
    background-color: #222;
    display: flex;
    flex-direction: column;
}

#main-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    height: 64px;
    background-color: #1a1a1a;
    border-bottom: 4px solid #000;
}

#main-panel-header h2 {
    font-size: 1rem;
    color: #888;
}

.user-status {
    background: #000;
    padding: 4px 12px;
    border: 2px solid #333;
    font-size: 0.9rem;
}

#main-panel-content {
    padding: 30px;
    overflow-y: auto;
    flex-grow: 1;
}

.placeholder-view {
    border: 4px solid #000;
    padding: 20px;
    background: #2a2a2a;
    box-shadow: 8px 8px 0px #000;
}
</style>
