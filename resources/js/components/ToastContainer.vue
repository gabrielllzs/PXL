<template>
    <div class="toast-container" aria-live="polite" role="status">
        <div v-for="t in toasts" :key="t.id" class="toast" :class="t.type" @click="removeToast(t.id)">
            <span class="msg">{{ t.message }}</span>
            <button class="close" @click.stop="removeToast(t.id)">×</button>
        </div>
    </div>
</template>

<script setup>
import { useToast } from '@/composables/useToast'
const { toasts, removeToast } = useToast()
</script>

<style scoped>
.toast-container {
    position: fixed;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    gap: 8px;
    pointer-events: none; /* allow clicks to pass through except on toasts */
}
.toast {
    pointer-events: auto;
    min-width: 220px;
    max-width: 420px;
    padding: 10px 12px;
    border-radius: 8px;
    background: rgba(255,255,255,0.95);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
}
.toast.info { border-left: 4px solid #2b6cb0; }
.toast.success { border-left: 4px solid #2f855a; }
.toast.error { border-left: 4px solid #c53030; }

.toast .close {
    background: transparent;
    border: none;
    cursor: pointer;
}
</style>
