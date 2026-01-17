<script setup>
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const { user, logout } = useAuth()
const { showToast } = useToast()

const showMenu = ref(false)

async function handleLogout() {
    const result = await logout()
    if (result.success) {
        showMenu.value = false
        showToast('Logged out successfully', 'success')
    }
}

function toggleMenu() {
    showMenu.value = !showMenu.value
}
</script>

<template>
    <div class="user-menu">
        <button
            @click="toggleMenu"
            class="auth-btn user-btn"
        >
            {{ user?.username || user?.name || 'User' }}
        </button>

        <div v-if="showMenu" class="user-dropdown">
            <div class="user-info">
                <p><strong>{{ user?.name }}</strong></p>
                <p class="user-email">{{ user?.email }}</p>
                <p v-if="user?.country" class="user-country">Country: {{ user.country }}</p>
            </div>
            <button @click="handleLogout" class="btn-logout">Logout</button>
        </div>
    </div>
</template>

<style scoped>
.user-menu {
    position: relative;
}

.auth-btn {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    pointer-events: auto;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    font-family: inherit;
}

.auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.1);
}

.user-btn {
    background: #10b981;
    border-color: rgba(16, 185, 129, 0.3);
    color: white;
    min-width: 160px;
    justify-content: center;
    font-weight: 500;
}

.user-btn:hover {
    background: #34d399;
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: white;
    border-radius: 12px;
    padding: 16px;
    min-width: 200px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    pointer-events: auto;
    z-index: 2;
}

.user-info {
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.user-info p {
    margin: 4px 0;
    font-size: 14px;
    color: #1e1e1e;
}

.user-email {
    color: #666;
    font-size: 12px;
}

.user-country {
    color: #666;
    font-size: 12px;
}

.btn-logout {
    width: 100%;
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}

.btn-logout:hover {
    background: #dc2626;
}
</style>
