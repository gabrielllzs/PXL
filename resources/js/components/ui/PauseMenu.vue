<script setup>
import { ref } from 'vue'

const open = ref(false)

function openMenu() {
    open.value = true
}

function resume() {
    open.value = false
}

function exitGame() {
    window.location.href = 'https://www.youtube.com/watch?v=xvFZjo5PgG0'
}

function support() {
    window.location.href = '/support'
}
</script>


<template>
    <div class="pause-button">
        <button
            @click="openMenu"
            >
            <img src="../../../images/settingsIcon.svg" alt="play icon">
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
                <li>Settings</li>
                <li>Info</li>
                <li @click="support">Support</li>
                <li @click="exitGame">Exit</li>
            </ul>
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
</style>
