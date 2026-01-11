<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
import { useWallet } from '@/composables/connectWallet'

const { showToast } = useToast()

const walletSignature = ref(null)

const { buyer, connecting, connect, loadCachedWallet } = useWallet()

async function handleConnect() {
    await connect(showToast)
}

onMounted(() => {
    loadCachedWallet()
})
</script>


<template>
    <div class="container">
        <button
            @click="handleConnect"
            class="wallet-btn connect-btn"
            :class="{ connecting: connecting, connected: buyer }"
            :disabled="connecting"
        >
            <img src="../../../images/phantom.svg" alt="phantom logo in pixel art" width="32" height="24" />
            <span v-if="connecting" class="status-text">
                <span class="loading-dot"></span>
                Connecting...
            </span>
            <span v-else-if="buyer" class="status-text">
                <span class="connected-indicator"></span>
                Connected
            </span>
            <span v-else class="status-text">Connect Wallet</span>
        </button>
        <div class="pump-btn">
            <div class="snowfall">
                <span class="flake flake-1"></span>
                <span class="flake flake-2"></span>
                <span class="flake flake-3"></span>
                <span class="flake flake-4"></span>
                <span class="flake flake-5"></span>
            </div>
            <a href="https://pump.fun">
                <img
                    src="../../../images/pump-logomark-christmas.webp"
                    alt="pump.fun logo in pixel art"
                    fetchpriority="high"
                    width="32"
                    height="32"
                />
            </a>
        </div>
    </div>
</template>

<style scoped>
.container {
    position: fixed;
    top: 16px;
    right: 16px;
    gap: 12px;
}

.wallet-btn {
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

.wallet-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.1);
}

.wallet-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.connect-btn {
    background: linear-gradient(135deg, #ab9ff2 0%, #9d8df1 100%);
    border-color: rgba(171, 159, 242, 0.3);
    color: white;
    font-weight: 500;
    min-width: 160px;
    justify-content: center;
}

.connect-btn:hover {
    background: linear-gradient(135deg, #b5a9f4 0%, #a897f3 100%);
    border-color: rgba(171, 159, 242, 0.5);
}

.connect-btn.connected {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-color: rgba(16, 185, 129, 0.3);
}

.connect-btn.connected:hover {
    background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
}

.connect-btn.connecting {
    cursor: wait;
    opacity: 0.8;
}

.connect-btn:disabled {
    cursor: not-allowed;
}

.pump-btn{
    display: flex;
    justify-content: flex-end;
    pointer-events: auto;
    padding: 10px;
}

.pump-btn img {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.pump-btn img:hover {
    transform: translateY(-2px);
}

.wallet-btn img {
    width: 32px;
    display: block;
    flex-shrink: 0;
}

.pump-btn img {
    width: 32px;
}

.snowfall {
    position: absolute;
    width: 32px;
    height: 32px;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
}

.flake {
    position: absolute;
    width: 2px;
    height: 2px;
    background: white;
    border-radius: 0;
    opacity: 0.9;
}

.flake-1 {
    left: 6px;
    animation: fall1 2s linear infinite;
}

.flake-2 {
    left: 12px;
    width: 1px;
    height: 1px;
    animation: fall2 2.5s linear infinite 0.3s;
    opacity: 0.7;
}

.flake-3 {
    left: 18px;
    animation: fall3 2.2s linear infinite 0.6s;
}

.flake-4 {
    left: 9px;
    width: 1px;
    height: 1px;
    animation: fall4 2.8s linear infinite 0.9s;
    opacity: 0.8;
}

.flake-5 {
    left: 22px;
    animation: fall5 2.3s linear infinite 1.2s;
}

@keyframes fall1 {
    0% {
        top: -2px;
        opacity: 0;
    }
    10% {
        opacity: 0.9;
    }
    90% {
        opacity: 0.9;
    }
    100% {
        top: 34px;
        opacity: 0;
    }
}

@keyframes fall2 {
    0% {
        top: -2px;
        opacity: 0;
    }
    10% {
        opacity: 0.7;
    }
    90% {
        opacity: 0.7;
    }
    100% {
        top: 34px;
        opacity: 0;
    }
}

@keyframes fall3 {
    0% {
        top: -2px;
        opacity: 0;
    }
    10% {
        opacity: 0.9;
    }
    90% {
        opacity: 0.9;
    }
    100% {
        top: 34px;
        opacity: 0;
    }
}

@keyframes fall4 {
    0% {
        top: -2px;
        opacity: 0;
    }
    10% {
        opacity: 0.8;
    }
    90% {
        opacity: 0.8;
    }
    100% {
        top: 34px;
        opacity: 0;
    }
}

@keyframes fall5 {
    0% {
        top: -2px;
        opacity: 0;
    }
    10% {
        opacity: 0.9;
    }
    90% {
        opacity: 0.9;
    }
    100% {
        top: 34px;
        opacity: 0;
    }
}

.status-text {
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    font-family: inherit;
}

.loading-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: white;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(0.8);
    }
}

.connected-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

/* Responsive design */
@media (max-width: 640px) {
    .container {
        top: 12px;
        right: 12px;
        gap: 8px;
    }

    .wallet-btn {
        padding: 10px 12px;
        border-radius: 10px;
    }

    .connect-btn {
        min-width: 140px;
    }
}
</style>
