<script setup>
import { ref, watch } from 'vue'
import { audioEnabled } from '../../composables/useAudio.js'

defineProps({
    hidden: {
        type: Boolean,
        default: false
    }
})

const isOpen = ref(false)
const isAudioOn = ref(audioEnabled.value)

watch(isAudioOn, (value) => {
    audioEnabled.value = value
})

watch(audioEnabled, (value) => {
    isAudioOn.value = value
})

function toggle() {
    isOpen.value = !isOpen.value
}

function close() {
    isOpen.value = false
}
</script>

<template>
    <div :class="{ 'mobile-hidden': hidden }">
        <button @click="toggle" class="menu-toggle" :class="{ active: isOpen }">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <div v-if="isOpen" class="menu-overlay" @click="close"></div>

        <div class="side-menu" :class="{ open: isOpen }">
            <div class="menu-header">
                <div class="logo-section">
                    <div class="logo-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <span class="logo-name">PXL</span>
                        <span class="logo-tagline">Place. Create. Together.</span>
                    </div>
                </div>
                <button @click="close" class="close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="menu-content">
                <div class="menu-section">
                    <h3 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="section-icon">
                            <path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z"/>
                        </svg>
                        How to Play
                    </h3>
                    <div class="info-cards">
                        <div class="info-card">
                            <div class="card-icon paint">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                                    <path d="M240-120q-45 0-89-22t-71-58q26 0 53-20.5t27-59.5q0-50 35-85t85-35q50 0 85 35t35 85q0 66-47 113t-113 47Zm230-240L360-470l358-358q11-11 27.5-11.5T774-828l54 54q12 12 12 28t-12 28L470-360Z"/>
                                </svg>
                            </div>
                            <div class="card-text">
                                <strong>Paint Pixels</strong>
                                <span>Click or hold SPACE to place pixels on the canvas</span>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="card-icon collaborate">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z"/>
                                </svg>
                            </div>
                            <div class="card-text">
                                <strong>Collaborate</strong>
                                <span>Create art together with the global community</span>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="card-icon level">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="card-text">
                                <strong>Level Up</strong>
                                <span>Place pixels to earn XP and unlock more capacity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="menu-section">
                    <h3 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="section-icon">
                            <path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 00-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 00-2.282.819l-.922 1.597a1.875 1.875 0 00.432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 000 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 00-.432 2.385l.922 1.597a1.875 1.875 0 002.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 002.28-.819l.923-1.597a1.875 1.875 0 00-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 000-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 00-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 00-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 00-1.85-1.567h-1.843zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" clip-rule="evenodd"/>
                        </svg>
                        Settings
                    </h3>
                    <div class="settings-list">
                        <label class="setting-item">
                            <div class="setting-info">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="setting-icon">
                                    <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.508c-1.141 0-2.318.664-2.66 1.905A9.76 9.76 0 001.5 12c0 .898.121 1.768.35 2.595.341 1.24 1.518 1.905 2.659 1.905h1.93l4.5 4.5c.945.945 2.561.276 2.561-1.06V4.06zM18.584 5.106a.75.75 0 011.06 0c3.808 3.807 3.808 9.98 0 13.788a.75.75 0 11-1.06-1.06 8.25 8.25 0 000-11.668.75.75 0 010-1.06z"/>
                                    <path d="M15.932 7.757a.75.75 0 011.061 0 6 6 0 010 8.486.75.75 0 01-1.06-1.061 4.5 4.5 0 000-6.364.75.75 0 010-1.06z"/>
                                </svg>
                                <span>Sound Effects</span>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" v-model="isAudioOn">
                                <span class="toggle-track">
                                    <span class="toggle-thumb"></span>
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="menu-section">
                    <h3 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="section-icon">
                            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm4.5 7.5a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 01.75-.75zm3.75-1.5a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0V12zm2.25-3a.75.75 0 01.75.75v6.75a.75.75 0 01-1.5 0V9.75A.75.75 0 0113.5 9zm3.75-1.5a.75.75 0 00-1.5 0v9a.75.75 0 001.5 0v-9z" clip-rule="evenodd"/>
                        </svg>
                        Quick Links
                    </h3>
                    <div class="quick-links">
                        <a href="/feedback" class="quick-link" @click="close">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0112 2.25c2.43 0 4.817.178 7.152.52 1.978.292 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.678-3.348 3.97a48.901 48.901 0 01-3.476.383.39.39 0 00-.297.17l-2.755 4.133a.75.75 0 01-1.248 0l-2.755-4.133a.39.39 0 00-.297-.17 48.9 48.9 0 01-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97z" clip-rule="evenodd"/>
                            </svg>
                            <span>Send Feedback</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="arrow-icon">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="menu-footer">
                <span class="version">v1.0.0</span>
                <span class="made-with">Made with ❤️</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.menu-toggle {
    position: absolute;
    top: 16px;
    left: 16px;
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    padding: 12px;
    pointer-events: auto;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.menu-toggle:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    border-color: rgba(0, 0, 0, 0.12);
}

.menu-toggle.active {
    background: #1e1e1e;
    border-color: #1e1e1e;
}

.menu-toggle .bar {
    width: 22px;
    height: 2.5px;
    background: #1e1e1e;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-toggle.active .bar {
    background: white;
}

.menu-toggle.active .bar:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.menu-toggle.active .bar:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}

.menu-toggle.active .bar:nth-child(3) {
    transform: rotate(-45deg) translate(5px, -5px);
}

.menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 4;
    pointer-events: auto;
}

.side-menu {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 340px;
    max-width: 90vw;
    background: #fafafa;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
    z-index: 5;
    transform: translateX(-100%);
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: auto;
    display: flex;
    flex-direction: column;
    font-family: 'pixel art', monospace;
}

.side-menu.open {
    transform: translateX(0);
}

.menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.logo-icon svg {
    width: 24px;
    height: 24px;
}

.logo-text {
    display: flex;
    flex-direction: column;
}

.logo-name {
    font-size: 20px;
    font-weight: 700;
    color: #1e1e1e;
    letter-spacing: -0.5px;
}

.logo-tagline {
    font-size: 11px;
    color: #888;
    font-weight: 500;
}

.close-btn {
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    transition: all 0.2s;
}

.close-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1e1e1e;
}

.close-btn svg {
    width: 20px;
    height: 20px;
}

.menu-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.menu-section {
    margin-bottom: 24px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px 0;
    font-size: 13px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-icon {
    width: 16px;
    height: 16px;
}

.info-cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.card-icon svg {
    width: 20px;
    height: 20px;
}

.card-icon.paint {
    background: #dbeafe;
    color: #2563eb;
}

.card-icon.collaborate {
    background: #d1fae5;
    color: #059669;
}

.card-icon.level {
    background: #fef3c7;
    color: #d97706;
}

.card-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.card-text strong {
    font-size: 14px;
    font-weight: 600;
    color: #1e1e1e;
}

.card-text span {
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.settings-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    cursor: pointer;
    transition: background 0.2s;
}

.setting-item:hover {
    background: #f9f9f9;
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.setting-icon {
    width: 20px;
    height: 20px;
    color: #666;
}

.setting-info span {
    font-size: 14px;
    font-weight: 500;
    color: #1e1e1e;
}

.toggle-switch {
    position: relative;
}

.toggle-switch input {
    display: none;
}

.toggle-track {
    width: 48px;
    height: 28px;
    background: #e5e7eb;
    border-radius: 14px;
    display: flex;
    align-items: center;
    padding: 2px;
    transition: background 0.25s;
    cursor: pointer;
}

.toggle-thumb {
    width: 24px;
    height: 24px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    transition: transform 0.25s;
}

.toggle-switch input:checked + .toggle-track {
    background: #2563eb;
}

.toggle-switch input:checked + .toggle-track .toggle-thumb {
    transform: translateX(20px);
}

.quick-links {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    text-decoration: none;
    color: #1e1e1e;
    transition: all 0.2s;
}

.quick-link:hover {
    background: #f5f5f5;
    transform: translateX(4px);
}

.quick-link svg {
    width: 20px;
    height: 20px;
    color: #666;
}

.quick-link span {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
}

.quick-link .arrow-icon {
    width: 16px;
    height: 16px;
    color: #ccc;
}

.menu-footer {
    padding: 16px 20px;
    background: white;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.version {
    font-size: 12px;
    color: #999;
    font-weight: 500;
}

.made-with {
    font-size: 12px;
    color: #999;
}

@media (max-width: 640px) {
    .mobile-hidden {
        display: none !important;
    }

    .menu-toggle {
        top: 12px;
        left: 12px;
        width: 44px;
        height: 44px;
    }

    .menu-toggle .bar {
        width: 20px;
    }

    .side-menu {
        width: 100%;
        max-width: 100%;
    }

    .menu-content {
        padding: 12px;
    }

    .info-card {
        padding: 12px;
    }
}
</style>
