<script setup>
import { ref, watch } from 'vue'
import { audioEnabled } from '../../composables/useAudio.js'
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
    <div>
        <!-- Toggle Button -->
        <button
            @click="toggle"
            class="menu-toggle"
            :class="{ active: isOpen }"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Overlay -->
        <div
            v-if="isOpen"
            class="menu-overlay"
            @click="close"
        ></div>

        <!-- Side Menu -->
        <div class="side-menu" :class="{ open: isOpen }">
            <div class="menu-header">
                <h2>Menu</h2>
                <button @click="close" class="close-btn">×</button>
            </div>
            <div class="menu-content">
                <div class="game-explanation">
                    <h3>How to Play</h3>
                    <p>Place pixels on the shared canvas to create art together with the community. Each pixel you place becomes part of a collaborative masterpiece.</p>

                    <h3>Cooldown</h3>
                    <p>There's a cooldown between placing pixels. Authenticated users have no cooldown, so consider creating an account!</p>

                    <h3>Navigation</h3>
                    <p>Use the zoom buttons and compass to navigate around the canvas. You can zoom in and out to see different areas of the world.</p>
                </div>

                <div class="audio-control">
                    <label class="ccs-toggle">
                        Sound effects :
                        <input type="checkbox" v-model="isAudioOn">
                        <span class="ccs-track">
                            <span class="ccs-thumb"></span>
                        </span>
                    </label>
                </div>

                <div class="menu-buttons">
                    <a href="/feedback" class="menu-btn" @click="close">
                        Feedback
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.menu-toggle {
    position: fixed;
    top: 16px;
    left: 16px;
    z-index: 1;
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    padding: 12px;
    pointer-events: auto;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.menu-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.1);
}

.menu-toggle.active {
    background: #2563eb;
    border-color: #1e40af;
}

.menu-toggle span {
    width: 24px;
    height: 3px;
    background: #1e1e1e;
    border-radius: 2px;
    transition: all 0.3s;
}

.menu-toggle.active span {
    background: white;
}

.menu-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(7px, 7px);
}

.menu-toggle.active span:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -7px);
}

.menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 3;
    pointer-events: auto;
}

.side-menu {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 300px;
    max-width: 85vw;
    background: white;
    box-shadow: 2px 0 16px rgba(0, 0, 0, 0.1);
    z-index: 3;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: auto;
    display: flex;
    flex-direction: column;
}

.side-menu.open {
    transform: translateX(0);
}

.menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.06);
}

.menu-header h2 {
    margin: 0;
    font-size: 24px;
    color: #1e1e1e;
}

.close-btn {
    background: none;
    border: none;
    font-size: 32px;
    color: #666;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.close-btn:hover {
    color: #1e1e1e;
}

.menu-content {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}
.game-explanation {
    margin-bottom: 24px;
}

.game-explanation h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    color: #1e1e1e;
    font-weight: 600;
}

.game-explanation p {
    margin: 0 0 20px 0;
    font-size: 14px;
    color: #666;
    line-height: 1.6;
}

.audio-control {
    padding-top: 20px;
    border-top: 2px solid rgba(0, 0, 0, 0.06);
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.menu-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 20px;
    border-top: 2px solid rgba(0, 0, 0, 0.06);
}

.menu-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    pointer-events: auto;
    font-size: 14px;
    text-decoration: none;
    display: block;
    text-align: center;
}

.menu-btn:hover {
    background: #3b82f6;
    transform: translateY(-1px);
}

.ccs-toggle {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.ccs-toggle input {
    display: none;
}

.ccs-track {
    width: 54px;
    height: 28px;
    background: #e5e7eb;
    border: 1px solid rgba(15, 23, 42, 0.5);
    display: grid;
    align-items: center;
    transition: 0.25s;
}

.ccs-thumb {
    width: 26px;
    height: 26px;
    background: #fff;
    box-shadow: 0 6px 14px rgba(2, 6, 23, 0.25);
    position: relative;
    transition: 0.25s;
}

.ccs-toggle input:checked + .ccs-track {
    background: #2563eb;
}

.ccs-toggle input:checked + .ccs-track .ccs-thumb {
    transform: translateX(28px);
}

/* Responsive */
@media (max-width: 640px) {
    .menu-toggle {
        top: 12px;
        left: 12px;
        width: 44px;
        height: 44px;
    }

    .side-menu {
        width: 280px;
    }
}
</style>
