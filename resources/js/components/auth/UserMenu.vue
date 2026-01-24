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
            showToast(`Level Up! You're now Level ${data.level}!`, 'success')
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
        <button @click="toggleMenu" class="user-btn" :class="{ active: showMenu }">
            <span class="user-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                </svg>
            </span>
            <span class="user-name">{{ user?.username || 'User' }}</span>
            <svg class="chevron" :class="{ rotated: showMenu }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
            </svg>
        </button>

        <Transition name="dropdown">
            <div v-if="showMenu" class="user-dropdown">
                <div class="dropdown-header">
                    <div class="header-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="header-info">
                        <span class="header-name">{{ user?.username || user?.name }}</span>
                        <span class="header-email">{{ user?.email }}</span>
                    </div>
                </div>
                
                <div v-if="pixelStatus" class="stats-section">
                    <div class="stat-card level-card">
                        <div class="stat-icon level-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-label">Level</span>
                            <span class="stat-value">{{ pixelStatus.level }}</span>
                        </div>
                    </div>
                    
                    <div class="stat-card pixels-card">
                        <div class="stat-icon pixels-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                                <path d="M240-120q-45 0-89-22t-71-58q26 0 53-20.5t27-59.5q0-50 35-85t85-35q50 0 85 35t35 85q0 66-47 113t-113 47Zm230-240L360-470l358-358q11-11 27.5-11.5T774-828l54 54q12 12 12 28t-12 28L470-360Z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-label">Pixels Available</span>
                            <span class="stat-value">{{ pixelStatus.pixels_available }}<span class="stat-max">/{{ pixelStatus.pixel_limit }}</span></span>
                        </div>
                    </div>
                    
                    <div v-if="pixelStatus.level_progress" class="level-progress-section">
                        <div class="progress-header">
                            <span class="progress-label">Progress to Level {{ pixelStatus.level_progress.next_level }}</span>
                            <span class="progress-value">{{ Math.floor(pixelStatus.level_progress.percentage) }}%</span>
                        </div>
                        <div class="level-progress-bar">
                            <div 
                                class="level-progress-fill" 
                                :style="{ width: `${pixelStatus.level_progress.percentage}%` }"
                            ></div>
                        </div>
                        <span class="xp-text">{{ pixelStatus.level_progress.progress }} / {{ pixelStatus.level_progress.needed }} XP</span>
                    </div>
                </div>
                
                <a v-if="user?.is_admin" href="/admin" class="btn-admin">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 6.75a5.25 5.25 0 016.775-5.025.75.75 0 01.313 1.248l-3.32 3.319c.063.475.276.934.641 1.299.365.365.824.578 1.3.64l3.318-3.319a.75.75 0 011.248.313 5.25 5.25 0 01-5.472 6.756c-1.018-.086-1.87.1-2.309.634L7.344 21.3A3.298 3.298 0 112.7 16.657l8.684-7.151c.533-.44.72-1.291.634-2.309A5.342 5.342 0 0112 6.75zM4.117 19.125a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75v-.008z" clip-rule="evenodd"/>
                    </svg>
                    Admin Panel
                </a>

                <button @click="handleLogout" class="btn-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd"/>
                    </svg>
                    Sign out
                </button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.user-menu {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    pointer-events: auto;
    font-family: 'pixel art', monospace;
}

.user-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    border-color: rgba(0, 0, 0, 0.12);
}

.user-btn.active {
    border-color: #2563eb;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.2);
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.user-avatar svg {
    width: 18px;
    height: 18px;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e1e1e;
}

.chevron {
    width: 16px;
    height: 16px;
    color: #888;
    transition: transform 0.2s ease;
}

.chevron.rotated {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: #fafafa;
    border-radius: 16px;
    min-width: 280px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    pointer-events: auto;
    z-index: 50;
    overflow: hidden;
    font-family: 'pixel art', monospace;
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.dropdown-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.header-avatar {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.header-avatar svg {
    width: 24px;
    height: 24px;
}

.header-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.header-name {
    font-size: 15px;
    font-weight: 600;
    color: #1e1e1e;
}

.header-email {
    font-size: 12px;
    color: #888;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stats-section {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon svg {
    width: 20px;
    height: 20px;
}

.level-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.pixels-icon {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.stat-label {
    font-size: 11px;
    color: #888;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #1e1e1e;
}

.stat-max {
    font-size: 13px;
    color: #888;
    font-weight: 500;
}

.level-progress-section {
    padding: 12px;
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.progress-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.progress-value {
    font-size: 12px;
    color: #1e1e1e;
    font-weight: 600;
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
    border-radius: 3px;
    transition: width 0.3s ease;
}

.xp-text {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: #888;
    text-align: right;
}

.btn-admin {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: calc(100% - 48px);
    margin: 0 12px 0px 12px;
    padding: 12px;
    background: #f3e8ff;
    color: #7c3aed;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-admin:hover {
    background: #ede9fe;
}

.btn-admin svg {
    width: 18px;
    height: 18px;
}

.btn-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: calc(100% - 24px);
    margin: 12px;
    padding: 12px;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-logout:hover {
    background: #fecaca;
}

.btn-logout svg {
    width: 18px;
    height: 18px;
}
</style>
