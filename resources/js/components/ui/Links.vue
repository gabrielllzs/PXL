<script setup>
// Social links component - pump.fun and telegram
</script>


<template>
    <div class="container">
        <div class="pump-btn short-btn">
            <div class="snowfall">
                <span class="flake flake-1"></span>
                <span class="flake flake-2"></span>
                <span class="flake flake-3"></span>
                <span class="flake flake-4"></span>
                <span class="flake flake-5"></span>
            </div>
            <a href="https://pump.fun/coin/rs4UYBUja3dJw5N4oKDSjuDAXRQiBGHQqX2SfsZpump">
                <img
                    src="../../../images/pump-logomark-christmas.webp"
                    alt="pump.fun logo in pixel art"
                    fetchpriority="high"
                    width="32"
                    height="32"
                />
            </a>
        </div>
        <div class="telegram-btn short-btn">
            <a href="https://t.me/pixels_g" target="_blank" rel="noopener noreferrer">
                <img src="../../../images/telegram.webp" alt="telegram logo in pixel art" width="32" height="32" />
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
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.short-btn {
    display: flex;
    justify-content: flex-end;
    pointer-events: auto;
    padding: 10px;
}

.short-btn img {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.short-btn img:hover {
    transform: translateY(-2px);
}

.wallet-btn img {
    width: 32px;
    display: block;
    flex-shrink: 0;
}

.short-btn img {
    width: 32px;
    height: 32px;
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

/* Responsive design */
@media (max-width: 640px) {
    .container {
        top: 12px;
        right: 12px;
        gap: 8px;
    }
}
</style>
