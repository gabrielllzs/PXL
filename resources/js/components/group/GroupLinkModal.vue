<script setup>
    import { ref, watch, toRefs } from 'vue'
    import { useToast } from '@/composables/useToast'
    
    const emit = defineEmits(['update:open'])
    const props = defineProps({
        open: { type: Boolean, default: false },
        inviteLink: { type: String, required: true }
    })
    const { open } = toRefs(props)
    const { showToast } = useToast()
    const isCopying = ref(false)
    
    function close() {
        emit('update:open', false)
    }
    
async function copyInviteLink() {
    if (!props.inviteLink) return
    isCopying.value = true
    try {
        await navigator.clipboard.writeText(props.inviteLink)
        showToast('Invite link copied', 'success')
    } catch {
        showToast('Could not copy link', 'error')
    } finally {
        isCopying.value = false
    }
}
    
    watch(open, val => {
        if (!val) isCopying.value = false
    })
</script>

<template>
    <div v-if="open" class="share-modal-backdrop" @click.self="close">
        <div class="modal-box" @click.stop>
            <button class="modal-close-btn" @click="close" type="button">✕</button>
            <h3 class="modal-title">Invite link</h3>
            <span class="modal-description">Send the link below to everybody you want to invite to the group</span>
            <div class="link-container">
                <div class="link-input-wrapper">
                    <input 
                        class="link-input" 
                        :value="inviteLink" 
                        readonly 
                    />
                    <button 
                        class="copy-btn" 
                        type="button" 
                        @click="copyInviteLink" 
                        :disabled="isCopying"
                    >
                        <span v-if="!isCopying">Copy</span>
                        <span v-else>Copying...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.share-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    pointer-events: auto;
}

.modal-box {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    width: 90%;
    max-width: 480px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    pointer-events: auto;
    font-family: 'pixel art', monospace;
}

.modal-close-btn {
    position: absolute;
    right: 8px;
    top: 8px;
    background: transparent;
    border: none;
    border-radius: 9999px;
    padding: 6px;
    font-size: 18px;
    line-height: 1;
    color: #4b5563;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 10;
}

.modal-close-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1e1e1e;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 18px;
    font-weight: 600;
    color: #1e1e1e;
    margin: 0 0 8px 0;
}

.modal-description {
    color: rgba(17, 24, 39, 0.8);
    font-size: 14px;
    display: block;
    margin-bottom: 16px;
}

.link-container {
    position: relative;
    margin-top: 16px;
}

.link-input-wrapper {
    border: 2px solid rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    position: relative;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 10px 6px 16px;
    background: white;
}

.link-input {
    color: #1e1e1e;
    min-width: 40px;
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    border: none;
    outline: none;
    background: transparent;
    padding: 8px 0;
    font-family: 'pixel art', monospace;
    letter-spacing: -0.3px;
}

.copy-btn {
    height: 40px;
    background: #111827;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
    white-space: nowrap;
}

.copy-btn:hover:not(:disabled) {
    background: #0b0f17;
}

.copy-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 640px) {
    .modal-box {
        width: 95%;
        max-width: 100%;
        border-radius: 12px;
    }

    .link-input-wrapper {
        flex-direction: column;
        padding: 12px;
        gap: 12px;
    }

    .link-input {
        width: 100%;
        text-align: center;
        padding: 12px;
        background: #f3f4f6;
        border-radius: 8px;
        font-size: 13px;
    }

    .copy-btn {
        width: 100%;
    }
}
</style>
