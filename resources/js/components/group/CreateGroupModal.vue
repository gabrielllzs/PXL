<script setup>
    import { ref, watch, nextTick } from 'vue'
    import { useToast } from '@/composables/useToast'
    
    const emit = defineEmits(['update:modelValue', 'created'])
    const props = defineProps({
        modelValue: {
            type: Boolean,
            default: false
        }
    })
    
    const { showToast } = useToast()
    const groupName = ref('')
    const isCreating = ref(false)
    const nameInput = ref(null)
    
    watch(() => props.modelValue, (isOpen) => {
        if (isOpen) {
            groupName.value = ''
            nextTick(() => {
                if (nameInput.value) {
                    nameInput.value.focus()
                }
            })
        }
    })
    
    function close() {
        emit('update:modelValue', false)
    }
    
    async function handleCreate() {
        if (!groupName.value.trim()) {
            showToast('Please enter a group name', 'error')
            return
        }
        if (groupName.value.length > 16) {
            showToast('Group name must be 16 characters or less', 'error')
            return
        }
        isCreating.value = true
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const res = await fetch('/api/group/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {})
                },
                body: JSON.stringify({ name: groupName.value.trim() })
            })
            if (!res.ok) {
                const error = await res.json().catch(() => ({ error: 'Failed to create group' }))
                throw new Error(error.error || 'Failed to create group')
            }
            showToast('Group created successfully', 'success')
            emit('created')
            close()
        } catch (e) {
            console.error(e)
            showToast(e.message || 'Could not create group', 'error')
        } finally {
            isCreating.value = false
        }
    }
    </script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-box" @click.stop>
            <button class="modal-close-btn" @click="close" type="button">✕</button>
            <h3 class="modal-title">Create group</h3>
            <form @submit.prevent="handleCreate">
                <div class="form-group">
                    <label class="label-text" for="group-name">Name</label>
                    <div class="input-wrapper">
                        <input 
                            id="group-name"
                            class="form-input" 
                            type="text" 
                            placeholder="Group Name" 
                            maxlength="16"
                            v-model="groupName"
                            :disabled="isCreating"
                            ref="nameInput"
                        />
                        <span class="char-count">{{ groupName.length }}/16</span>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" @click="close" :disabled="isCreating">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isCreating || !groupName.trim()">
                        <span v-if="!isCreating">Create</span>
                        <span v-else>Creating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    pointer-events: auto;
}

.modal-box {
    position: relative;
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    max-width: 480px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    pointer-events: auto;
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
    margin: 0 0 20px 0;
}

.form-group {
    margin-bottom: 24px;
}

.label-text {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #1e1e1e;
    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
    width: 100%;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 2px solid rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #1e1e1e;
    background: white;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #111827;
}

.form-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.char-count {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    color: #999;
    pointer-events: none;
}

.form-input:focus ~ .char-count {
    color: #666;
}

.form-actions {
    display: flex;
    width: 100%;
    justify-content: flex-end;
    gap: 12px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background-color 0.15s ease;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f3f4f6;
    color: #111827;
}

.btn-secondary:hover:not(:disabled) {
    background: #e5e7eb;
}

.btn-primary {
    background: #111827;
    color: #fff;
}

.btn-primary:hover:not(:disabled) {
    background: #0b0f17;
}
</style>
