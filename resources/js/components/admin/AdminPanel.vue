<script setup>
import { ref, watch, onMounted } from 'vue'
import Dashboard from './Dashboard.vue'
import UserBans from './UserBans.vue';
import SupportTickets from "./SupportTickets.vue";
import ToastContainer from '@/components/ToastContainer.vue';

const STORAGE_KEY = 'admin:activePage'
const validPages = ['dashboard', 'user bans', 'Feedback tickets']

const user = ref(null)
const activePage = ref(localStorage.getItem(STORAGE_KEY) || 'dashboard')
const sidebarOpen = ref(false)

// Validate stored page
if (!validPages.includes(activePage.value)) {
    activePage.value = 'dashboard'
}

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

watch(activePage, (page) => {
    localStorage.setItem(STORAGE_KEY, page)
    // Close sidebar on mobile when page changes
    if (window.innerWidth <= 768) {
        sidebarOpen.value = false
    }
})

onMounted(async () => {
    const response = await fetch('/api/me', { credentials: 'include' })
    if (response.ok) user.value = await response.json()
})

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
    <div class="container">
        <div id="side-panel" :class="{ open: sidebarOpen }">
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
                    <li  :class="{ active: activePage === 'Feedback tickets' }"  @click="activePage = 'Feedback tickets'">
                        <span>> Support Tickets</span>
                    </li>
                    <li>
                        <a href="/" class="menu-link">
                            <span>>Return</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div id="main-panel">
            <div id="main-panel-header">
                <button class="menu-toggle" @click="toggleSidebar" aria-label="Toggle menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h2>//{{ activePage }}</h2>
                <p v-if="user" class="user-status">
                   {{ user.username }}
                </p>
            </div>
            <div id="main-panel-content">
                <Dashboard v-if="activePage === 'dashboard'" />
                <UserBans v-if="activePage === 'user bans'" />
                <SupportTickets v-if="activePage === 'Feedback tickets'" />
            </div>
        </div>
        <div v-if="sidebarOpen" class="sidebar-overlay" @click="sidebarOpen = false"></div>
        <ToastContainer />
    </div>
</template>

<style scoped>
.container {
    display: flex;
    height: 100%;
    width: 100%;
    position: relative;
    font-family: 'pixel art', monospace;
}

#side-panel {
    width: 260px;
    height: 100%;
    background-color: #dfdfdf;
    transition: transform 0.3s ease;
    z-index: 100;
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
    margin-bottom: 20px;
    height: 90%;
    display: flex;
}

#side-panel-content ul {
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
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

.menu-link {
    color: inherit;
    text-decoration: none;
    display: block;
    width: 100%;
}

/* Main Panel */
#main-panel {
    flex-grow: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

#main-panel-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    min-height: 64px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #ddd;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: #333;
    align-items: center;
    justify-content: center;
    font-family: 'pixel art', monospace;
}

.menu-toggle svg {
    width: 24px;
    height: 24px;
}

#main-panel-header h2 {
    font-size: 1.1rem;
    margin: 0;
    flex: 1;
}

.user-status {
    font-size: 0.9rem;
    margin: 0;
}

#main-panel-content {
    padding: 20px;
    overflow-y: auto;
    flex-grow: 1;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 99;
}

.placeholder-view {
    padding: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    #side-panel {
        position: fixed;
        left: 0;
        top: 0;
        transform: translateX(-100%);
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    }

    #side-panel.open {
        transform: translateX(0);
    }

    .menu-toggle {
        display: flex;
    }

    .sidebar-overlay {
        display: block;
    }

    #main-panel-header {
        padding: 12px 16px;
    }

    #main-panel-header h2 {
        font-size: 1rem;
    }

    .user-status {
        font-size: 0.85rem;
    }

    #main-panel-content {
        padding: 16px;
    }

    #side-panel-logo h2 {
        font-size: 20px;
    }

    #side-panel-content li {
        padding: 12px 20px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    #main-panel-header {
        padding: 10px 12px;
        min-height: 56px;
    }

    #main-panel-header h2 {
        font-size: 0.9rem;
    }

    .user-status {
        display: none;
    }

    #main-panel-content {
        padding: 12px;
    }

    #side-panel {
        width: 240px;
    }
}
</style>
