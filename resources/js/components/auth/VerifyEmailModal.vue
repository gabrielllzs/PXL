<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    email: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue', 'success'])

const { checkAuth, loading } = useAuth()
const { showToast } = useToast()

const verificationCode = ref('')
const resendingCode = ref(false)

async function handleVerify() {
    if (verificationCode.value.length !== 6) return
    
    try {
        const response = await axios.post('/api/verify-email', {
            code: verificationCode.value
        })
        
        if (response.data.success) {
            showToast('Email verified successfully!', 'success')
            verificationCode.value = ''
            await checkAuth()
            emit('success')
            emit('update:modelValue', false)
        }
    } catch (err) {
        showToast(err.response?.data?.error || err.response?.data?.message || 'Verification failed', 'error')
    }
}

async function resendCode() {
    resendingCode.value = true
    try {
        const response = await axios.post('/api/resend-verification')
        if (response.data.success) {
            showToast('Verification code resent!', 'success')
        }
    } catch (err) {
        showToast(err.response?.data?.error || err.response?.data?.message || 'Failed to resend code', 'error')
    } finally {
        resendingCode.value = false
    }
}

function close() {
    emit('update:modelValue', false)
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <h2>Verify Your Email</h2>
            <p class="verification-info">
                We've sent a 6-digit verification code to <strong>{{ email }}</strong>
            </p>
            <form @submit.prevent="handleVerify">
                <div class="form-group">
                    <label>Verification Code</label>
                    <input
                        v-model="verificationCode"
                        type="text"
                        required
                        maxlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        class="verification-input"
                        @input="verificationCode = verificationCode.replace(/[^0-9]/g, '')"
                    />
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary" :disabled="loading || verificationCode.length !== 6">
                        {{ loading ? 'Verifying...' : 'Verify Email' }}
                    </button>
                    <button 
                        type="button" 
                        class="btn-link" 
                        @click="resendCode"
                        :disabled="resendingCode"
                    >
                        {{ resendingCode ? 'Sending...' : "Didn't receive code? Resend" }}
                    </button>
                </div>
            </form>
            <button class="modal-close" @click="close">×</button>
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
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    pointer-events: auto;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-content h2 {
    margin: 0 0 20px 0;
    color: #1e1e1e;
    font-size: 24px;
}

.verification-info {
    margin-bottom: 20px;
    color: #666;
    font-size: 14px;
    text-align: center;
}

.verification-info strong {
    color: #1e1e1e;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    color: #1e1e1e;
    font-weight: 500;
    font-size: 14px;
}

.verification-input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    font-size: 24px;
    letter-spacing: 8px;
    text-align: center;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.verification-input:focus {
    outline: none;
    border-color: #ab9ff2;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 20px;
}

.btn-primary {
    background: #ab9ff2;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
    background: #b5a9f4;
    transform: translateY(-1px);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-link {
    background: none;
    border: none;
    color: #ab9ff2;
    cursor: pointer;
    text-decoration: underline;
    font-size: 14px;
    padding: 0;
}

.btn-link:hover:not(:disabled) {
    color: #9d8df1;
}

.btn-link:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    background: none;
    border: none;
    font-size: 28px;
    color: #666;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #1e1e1e;
}
</style>
