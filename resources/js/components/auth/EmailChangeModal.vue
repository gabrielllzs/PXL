<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'success'])

const { checkAuth, loading } = useAuth()
const { showToast } = useToast()

const newEmail = ref('')
const password = ref('')
const code = ref('')
const step = ref('email') // 'email', 'code'
const changing = ref(false)

function resetForm() {
    newEmail.value = ''
    password.value = ''
    code.value = ''
    step.value = 'email'
}

async function requestChange() {
    if (!newEmail.value || !password.value) return
    
    changing.value = true
    try {
        const response = await axios.post('/change-email', {
            new_email: newEmail.value,
            password: password.value
        })
        
        if (response.data.success) {
            showToast('Verification code sent to your new email', 'success')
            step.value = 'code'
        }
    } catch (err) {
        showToast(err.response?.data?.error || 'Failed to send verification code', 'error')
    } finally {
        changing.value = false
    }
}

async function verifyChange() {
    if (code.value.length !== 6) return
    
    changing.value = true
    try {
        const response = await axios.post('/verify-email-change', {
            code: code.value
        })
        
        if (response.data.success) {
            showToast('Email changed successfully!', 'success')
            await checkAuth()
            emit('success')
            emit('update:modelValue', false)
            resetForm()
        }
    } catch (err) {
        showToast(err.response?.data?.error || 'Verification failed', 'error')
    } finally {
        changing.value = false
    }
}

function close() {
    emit('update:modelValue', false)
    resetForm()
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <button class="modal-close" @click="close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            
            <div class="modal-header">
                <h2>Change Email</h2>
                <p class="header-subtitle" v-if="step === 'email'">Enter your new email and current password</p>
                <p class="header-subtitle" v-else>Enter the 6-digit code sent to {{ newEmail }}</p>
            </div>
            
            <form @submit.prevent="step === 'email' ? requestChange() : verifyChange()" class="auth-form">
                <!-- Email Step -->
                <template v-if="step === 'email'">
                    <div class="form-group">
                        <label for="new-email">New Email</label>
                        <div class="input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="input-icon">
                                <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                                <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
                            </svg>
                            <input 
                                id="new-email"
                                v-model="newEmail" 
                                type="email" 
                                required 
                                autocomplete="email"
                                placeholder="new@example.com"
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Current Password</label>
                        <div class="input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="input-icon">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/>
                            </svg>
                            <input 
                                id="password"
                                v-model="password" 
                                type="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                        </div>
                    </div>
                </template>
                
                <!-- Code Step -->
                <div v-if="step === 'code'" class="form-group">
                    <label>Verification Code</label>
                    <input
                        v-model="code"
                        type="text"
                        required
                        maxlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        class="verification-input"
                        @input="code = code.replace(/[^0-9]/g, '')"
                    />
                </div>
                
                <button type="submit" class="btn-primary" :disabled="changing || loading">
                    <span v-if="!changing && !loading">
                        {{ step === 'email' ? 'Send Code' : 'Verify & Change' }}
                    </span>
                    <span v-else class="loading-state">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="31.4 31.4" />
                        </svg>
                        {{ step === 'email' ? 'Sending...' : 'Verifying...' }}
                    </span>
                </button>
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
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    pointer-events: auto;
}

.modal-content {
    background: #fafafa;
    border-radius: 20px;
    padding: 32px;
    max-width: 420px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    font-family: 'pixel art', monospace;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(0, 0, 0, 0.05);
    border: none;
    color: #666;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    transition: all 0.2s;
    z-index: 10;
}

.modal-close:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #1e1e1e;
}

.modal-close svg {
    width: 22px;
    height: 22px;
}

.modal-header {
    text-align: center;
    margin-bottom: 24px;
}

.modal-header h2 {
    margin: 0 0 6px 0;
    color: #1e1e1e;
    font-size: 24px;
    font-weight: 700;
}

.header-subtitle {
    margin: 0;
    color: #888;
    font-size: 14px;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    color: #1e1e1e;
    font-weight: 600;
    font-size: 13px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #888;
    pointer-events: none;
}

.form-group input {
    width: 100%;
    padding: 14px 14px 14px 44px;
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    color: #1e1e1e;
    background: white;
    transition: all 0.2s;
    box-sizing: border-box;
    font-family: 'pixel art', monospace;
}

.form-group input::placeholder {
    color: #bbb;
}

.form-group input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.verification-input {
    padding: 14px 12px !important;
    padding-left: 12px !important;
    font-size: 24px;
    letter-spacing: 8px;
    text-align: center;
    font-weight: 600;
}

.btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(59, 130, 246, 0.3);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 12px rgba(59, 130, 246, 0.15);
    font-family: 'pixel art', monospace;
    font-weight: 600;
    font-size: 15px;
    color: #2563eb;
    margin-top: 4px;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
    border-color: rgba(59, 130, 246, 0.5);
    background: rgba(239, 246, 255, 0.95);
}

.btn-primary:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.spinner {
    width: 18px;
    height: 18px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
    .modal-content {
        width: 100%;
        height: 100%;
        border-radius: 0;
        padding: 24px 20px;
        padding-top: 80px;
    }
}
</style>
