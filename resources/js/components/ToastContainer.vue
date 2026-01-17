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
    bottom: 16px;
    left: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    pointer-events: none;
    z-index: 3;
    max-width: 400px;
}

.toast {
    pointer-events: auto;
    padding: 12px 16px;
    border-radius: 8px;
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 14px;
    color: #1e1e1e;
    min-width: 250px;
    animation: slideInLeft 0.3s ease-out;
}

@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast.info {
    border-left: 4px solid #2b6cb0;
}

.toast.success {
    border-left: 4px solid #1cbc00;
}

.toast.error {
    border-left: 4px solid #ac0000;
}

.toast .msg {
    flex: 1;
    line-height: 1.4;
}

.toast .close {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #666;
    font-size: 20px;
    line-height: 1;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: color 0.2s;
}

.toast .close:hover {
    color: #1e1e1e;
}

/* Responsive */
@media (max-width: 640px) {
    .toast-container {
        bottom: 12px;
        left: 12px;
        right: 12px;
        max-width: none;
    }
    
    .toast {
        min-width: auto;
        width: 100%;
    }
}
</style>
