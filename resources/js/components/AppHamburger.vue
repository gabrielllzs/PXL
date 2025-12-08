<script setup>
import { ref } from 'vue'

const isMenuOpen = ref(false)
const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value
}
</script>

<template>
    <button @click="toggleMenu" class="wallet-btn hamburger-btn" aria-label="Toggle menu">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="menu-icon">
            <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <div v-if="isMenuOpen" class="rules-menu" @click="toggleMenu">
        <div class="content-menu" @click.stop>
            <div class="header">
                <h1>Welcome to Pixel Place</h1>
                <button class="close-btn" @click="toggleMenu" aria-label="Close menu">×</button>
            </div>
            <p>
                Place a colored pixel onto a shared canvas with other players online.
                The cooldown is <strong>180 seconds</strong> between placements.
                Token holders can connect their Phantom wallet for a reduced
                <strong>60-second cooldown</strong>.
            </p>

            <h2>Rules</h2>
            <p>Please follow these guidelines to help keep the experience enjoyable for everyone.</p>

            <h3>1. Minimal NSFW Content</h3>
            <p>
                Keep adult or explicit content to a minimum. Excessive violations may result in atemporary ban.
            </p>

            <h3>2. Proxies, VPNs, and Bots</h3>
            <p>
                VPNs and proxies are allowed for normal use.
                If the system detects timer evasion or rapid automated pixel placement, it will result in a non-negotiable ban.
            </p>
            <p>Bots result in an immediate permanent ban.</p>

            <h3>3. Reports</h3>
            <p>
                If you see behavior that violates the rules, submit a report.
                You may only submit one ticket, so include sufficient detail:
                a screenshot, the exact <strong>x,y</strong> coordinates, and the reason for reporting.
            </p>
            <p>
                Reports are reviewed manually. Actions may include pixel removal, temporary suspension, or permanent bans.
            </p>

            <br>
            <p>The Pixel Place team thanks you for your contribution. Enjoy your time here.</p>
        </div>
    </div>

    <a href="https://pump.fun" class="wallet-btn" target="_blank" rel="noopener noreferrer" aria-label="Open pump.fun">
        <img src="../../images/pump-logomark-christmas.webp" alt="pump.fun logo in pixel art" />
    </a>
</template>

<style scoped>
.wallet-btn {
    position: fixed;
    top: 60px;
    left: 10px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    pointer-events: auto;
    color: black;
}

.wallet-btn:nth-child(2) {
    top: 10px;
}

.wallet-btn img {
    width: 26px;
    display: block;
}

.wallet-btn:nth-child(2) img {
    width: 36px;
}

.hamburger-btn svg {
    width: 26px;
    height: 26px;
}

.rules-menu {
    position: fixed;
    width: 100vw;
    height: 100vh;
    z-index: 10;
    pointer-events: auto;
    cursor: pointer;
    background-color: rgba(0, 0, 0, 0.5);
    font-family: Proxima Nova, Arial, sans-serif;
}

.content-menu {
    background: white;
    padding: 20px;
    max-width: 900px;
    max-height: 100vh;
    border-radius: 8px;
    margin: 50px auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: #374151;
    overflow-y: auto;
}

.rules-menu h1 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 12px;
}

.rules-menu h2 {
    font-size: 20px;
    margin: 20px 0 8px;
}

.rules-menu h3 {
    font-size: 16px;
    margin: 16px 0 6px;
    font-weight: bold;
    color: #555;
}

.rules-menu p {
    margin: 6px 0;
    line-height: 1.4;
}

.rules-menu a {
    color: #3b82f6;
    text-decoration: underline;
}

.close-btn {
    position: absolute;
    top: 0;
    right: 0;
    font-size: 28px;
    background: none;
    border: none;
    cursor: pointer;
    line-height: 1;
    padding: 4px 8px;
    color: #374151;
    transition: color 0.2s;
}

.close-btn:hover {
    color: #ef4444;
}


.header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-right: 40px;
}


.header h1 {
    margin: 0;
}
</style>
