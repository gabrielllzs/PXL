<script setup>
defineProps({
    raised: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['zoomIn', 'zoomOut', 'center'])
</script>

<template>
    <div class="zoom-controls" :class="{ raised }">
        <div class="zoom-buttons">
            <button @click="emit('zoomIn')" title="Zoom In">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
            </button>
            <button @click="emit('zoomOut')" title="Zoom Out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
            </button>
        </div>
        <button class="center-btn" @click="emit('center')" title="Center Map">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3" />
                <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
            </svg>
        </button>
    </div>
</template>

<style scoped>
.zoom-controls {
    position: absolute;
    bottom: 20px;
    right: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    pointer-events: auto;
    transition: bottom 0.25s ease;
}

.zoom-controls.raised {
    bottom: 220px;
}

.zoom-buttons {
    display: flex;
    flex-direction: column;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.zoom-buttons button {
    width: 44px;
    height: 36px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    transition: background 0.2s;
}

.zoom-buttons button:first-child {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.zoom-buttons button:hover {
    background: rgba(0, 0, 0, 0.05);
}

.zoom-buttons button svg {
    width: 20px;
    height: 20px;
}

.center-btn {
    width: 44px;
    height: 44px;
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.center-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    background: #fff;
}

.center-btn:active {
    transform: translateY(0);
}

.center-btn svg {
    width: 22px;
    height: 22px;
}

@media (max-width: 768px) {
    .zoom-buttons {
        display: none;
    }

    .zoom-controls {
        bottom: 120px;
    }

    .zoom-controls.raised {
        bottom: 340px;
    }
}
</style>
