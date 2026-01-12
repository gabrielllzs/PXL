<script setup>
import { ref, watch, onMounted } from 'vue'
import { audioEnabled } from '../../composables/useAudio.js'

const open = ref(false)
const settingsOpen = ref(false)
const isAudioOn = ref(true)
const infoOpen = ref(false)

function openMenu() {
    open.value = true
}

function resume() {
    open.value = false
}

function toggleSettingsMenu() {
    settingsOpen.value = !settingsOpen.value
    open.value = !settingsOpen.value;
}

function toggleInfoMenu() {
    infoOpen.value = !infoOpen.value
    open.value = !infoOpen.value;
}

function exitGame() {
    window.location.href = 'https://www.youtube.com/watch?v=xvFZjo5PgG0'
}

function support() {
    window.location.href = '/support'
}

watch(isAudioOn, (value) => {
    audioEnabled.value = value
});

const nameText = 'gabriel';

</script>


<template>
    <div class="pause-button">
        <button
            @click="openMenu"
            >
            <img src="../../../images/settingsIcon.svg" alt="play icon" width="24" height="24">
        </button>
        <p>
            pause
        </p>
    </div>

    <div v-if="open" class="pause-menu">
        <h1>Game Paused</h1>
        <p>The game is currently paused. Press any key to resume.</p>

        <div class="game-options">
            <ul>
                <li @click="resume">Resume Game</li>
                <li @click="toggleSettingsMenu">Settings</li>
                <li @click="toggleInfoMenu">Info</li>
                <li @click="support">Support</li>
                <li @click="exitGame">Exit</li>
            </ul>
        </div>
    </div>

    <div v-if="settingsOpen" class="settings-menu">
        <div class="settings-container">
            <div class="header">
                <button @click="toggleSettingsMenu">x</button>
            </div>
            <label class="ccs-toggle">
                audio :
                <input type="checkbox" v-model="isAudioOn">
                <span class="ccs-track">
                    <span class="ccs-thumb">
                    </span>
                </span>
            </label>
        </div>
    </div>

    <div v-if="infoOpen" class="info-menu">
        <div class="info-container">
            <div class="header">
                <button @click="toggleInfoMenu">x</button>
            </div>
            <h2>Game Info</h2>
            <div class="info-intro">
                This game is developed by<div class="wave-text">
            <span v-for="(char, index) in nameText.split('')" :key="index" :style="{ animationDelay: `${index * 0.1}s`, animationFillMode: 'both' }">
                {{ char }}
            </span>
            </div>
            </div>
            <p>Enjoy playing!</p>
        </div>
    </div>
</template>

<style scoped>
.pause-menu {
    position: absolute;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    pointer-events: all;
}

.game-options ul {
    list-style: none;
    padding: 0;
}

.game-options li {
    margin: 10px 0;
    cursor: pointer;
    text-decoration: none;
}

.game-options li::before {
    content: "> ";
    left: 0;
    opacity: 0;
    transition: opacity 0.1s ease;
}

.game-options li:hover::before {
    opacity: 1;
}

.game-options li:hover {
    transform: scale(1.1);
}

.pause-button {
    display: flex;
    align-items: center;
    gap: 4px;
    position: fixed;
    top: 8px;
    left: 8px;
    pointer-events: auto;
    font-family: 'pixel art', serif;
    color: white;
    text-align: center;
    font-size: 16px;
}

.pause-button button {
    background: transparent;
    border: none;
    cursor: pointer;
    width: 100%;
    height: 100%;

}

.pause-button button:hover {
    transform: scale(1.1);
}

.settings-menu{
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    pointer-events: all;
    position: absolute;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.settings-container {
    background: rgba(0, 0, 0, 0.8);
    width: 200px;
    height: 75px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    padding: 16px;
}

.header button{
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
}

.header {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 12px;
}

.info-menu{
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    pointer-events: all;
    position: absolute;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.info-intro {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Custom CSS Switch */

.ccs-toggle{
    display: flex;
    justify-content: center;
    align-items: center;
    gap:8px;
    cursor: pointer;
}

.ccs-toggle input{ display:none; }

.ccs-track{
    width:54px; height:28px;
    background:#e5e7eb;
    border:1px solid rgba(15,23,42,.5);
    display:grid;
    align-items:center;
    transition:.25s;
}

.ccs-thumb{
    width:26px; height:26px;
    background:#fff;
    box-shadow:0 6px 14px rgba(2,6,23,.25);
    position:relative;
    transition:.25s;
}

.ccs-thumb::after{
    position:absolute; inset:0;
    display:grid;
    place-items:center;
    font-size:12px;
    transition:.25s;
}

.ccs-toggle input:checked + .ccs-track{
    background:#2563eb;
}

.ccs-toggle input:checked + .ccs-track .ccs-thumb{
    transform:translateX(28px);
}

.ccs-toggle input:checked + .ccs-track .ccs-thumb::after{
    color:#2563eb;
}


/* rainbow wave text */

.wave-text {
    display: flex;
}

.wave-text span {
    display: inline-block;
    animation: rainbow 3s linear infinite;
    animation-fill-mode: both; /* ensures delay works */
}


@keyframes rainbow {
    0% { color: #ff0000; } /* Red */
    14% { color: #ff7f00; } /* Orange */
    28% { color: #ffff00; } /* Yellow */
    42% { color: #00ff00; } /* Green */
    57% { color: #0000ff; } /* Blue */
    71% { color: #4b0082; } /* Indigo */
    85% { color: #9400d3; } /* Violet */
    100% { color: #ff0000; } /* Back to Red */
}
</style>
