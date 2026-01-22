<template>
    <Teleport to="body">
        <TransitionGroup name="toast" tag="div" class="toast-container" aria-live="polite" role="status">
            <div v-for="t in toasts" :key="t.id" class="toast" :class="t.type">
                <div class="toast-icon">
                    <svg v-if="t.type === 'success'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else-if="t.type === 'error'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span class="toast-msg">{{ t.message }}</span>
                <button class="toast-close" @click="removeToast(t.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'
const { toasts, removeToast } = useToast()
</script>

<style scoped>
.toast-container {
    position: fixed;
    bottom: 20px;
    left: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
    z-index: 9999;
    max-width: 380px;
}

.toast {
    pointer-events: auto;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #1e1e1e;
    min-width: 280px;
    cursor: default;
    font-family: 'pixel art', monospace;
}

.toast-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.toast-icon svg {
    width: 20px;
    height: 20px;
}

.toast.info .toast-icon {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}

.toast.success .toast-icon {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.toast.error .toast-icon {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
}

.toast-msg {
    flex: 1;
    line-height: 1.4;
    font-weight: 500;
}

.toast-close {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #999;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
    border-radius: 6px;
}

.toast-close svg {
    width: 16px;
    height: 16px;
}

.toast-close:hover {
    color: #1e1e1e;
    background: rgba(0, 0, 0, 0.05);
}

/* Transitions */
.toast-enter-active {
    animation: slideIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-leave-active {
    animation: slideOut 0.25s ease-in forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(-100%) scale(0.9);
        opacity: 0;
    }
    to {
        transform: translateX(0) scale(1);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0) scale(1);
        opacity: 1;
    }
    to {
        transform: translateX(-100%) scale(0.9);
        opacity: 0;
    }
}

/* Responsive */
@media (max-width: 640px) {
    .toast-container {
        bottom: auto;
        top: 70px;
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
