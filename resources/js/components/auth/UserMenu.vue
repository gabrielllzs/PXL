<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import axios from 'axios'

const { user, logout } = useAuth()
const { showToast } = useToast()

const showMenu = ref(false)
const pixelStatus = ref(null)
const pixelStatusInterval = ref(null)

async function fetchPixelStatus() {
    if (!user.value) return
    
    try {
        const { data } = await axios.get('/api/pixel-status')
        const oldLevel = pixelStatus.value?.level
        pixelStatus.value = data
        
        if (oldLevel && data.level > oldLevel) {
            showToast(`Level Up! You're now Level ${data.level}! 🎉`, 'success')
        }
    } catch (err) {
        if (err.response?.status !== 429) {
            console.error('Failed to fetch pixel status:', err)
        }
    }
}

function startPolling() {
    if (pixelStatusInterval.value) {
        clearInterval(pixelStatusInterval.value)
    }
    fetchPixelStatus()
    pixelStatusInterval.value = setInterval(fetchPixelStatus, 10000)
}

function stopPolling() {
    if (pixelStatusInterval.value) {
        clearInterval(pixelStatusInterval.value)
        pixelStatusInterval.value = null
    }
}

async function handleLogout() {
    const result = await logout()
    if (result.success) {
        showMenu.value = false
        pixelStatus.value = null
        stopPolling()
        showToast('Logged out successfully', 'success')
    }
}

function toggleMenu() {
    showMenu.value = !showMenu.value
    if (showMenu.value && user.value) {
        fetchPixelStatus()
    }
}

watch(user, (newUser) => {
    if (newUser) {
        startPolling()
    } else {
        stopPolling()
        pixelStatus.value = null
    }
}, { immediate: true })

onUnmounted(() => {
    stopPolling()
})
</script>

<template>
    <div class="user-menu">
        <button
            @click="toggleMenu"
            class="auth-btn user-btn"
        >
            {{ user?.username || 'User' }}
        </button>

        <div v-if="showMenu" class="user-dropdown">
            <div class="user-info">
                <p><strong>{{ user?.name }}</strong></p>
                <p class="user-email">{{ user?.email }}</p>
                <p v-if="user?.country" class="user-country">Country: {{ user.country }}</p>
            </div>
            
            <div v-if="pixelStatus" class="level-info">
                <div class="level-header">
                    <span class="level-badge">Level {{ pixelStatus.level }}</span>
                    <span class="pixel-count">{{ pixelStatus.pixels_available }}/{{ pixelStatus.pixel_limit }}</span>
                </div>
                <div class="pixel-progress-bar">
                    <div 
                        class="pixel-progress-fill" 
                        :style="{ width: `${(pixelStatus.pixels_available / pixelStatus.pixel_limit) * 100}%` }"
                    ></div>
                </div>
                <div v-if="pixelStatus.time_until_regeneration > 0" class="regen-timer">
                    Refill in {{ Math.ceil(pixelStatus.time_until_regeneration) }}s
                </div>
                <div v-if="pixelStatus.level_progress" class="level-progress">
                    <span class="progress-text">
                        {{ pixelStatus.level_progress.progress }} / {{ pixelStatus.level_progress.needed }} pixels to Level {{ pixelStatus.level_progress.next_level }}
                    </span>
                    <div class="level-progress-bar">
                        <div 
                            class="level-progress-fill" 
                            :style="{ width: `${pixelStatus.level_progress.percentage}%` }"
                        ></div>
                    </div>
                </div>
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

.level-info {
    margin: 16px 0;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.level-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.level-badge {
    font-weight: 600;
    font-size: 16px;
    color: #2563eb;
}

.pixel-count {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.pixel-progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.regen-timer {
    font-size: 12px;
    color: #f59e0b;
    font-weight: 500;
    margin-bottom: 8px;
}

.pixel-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #22c55e 0%, #10b981 100%);
    transition: width 0.3s ease;
}

.level-progress {
    margin-top: 8px;
}

.progress-text {
    font-size: 12px;
    color: #666;
    display: block;
    margin-bottom: 4px;
}

.level-progress-bar {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.level-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    transition: width 0.3s ease;
}
</style>
