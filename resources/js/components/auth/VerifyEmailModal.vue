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

const emit = defineEmits(['update:modelValue', 'success', 'changeEmail'])

const { checkAuth, loading } = useAuth()
const { showToast } = useToast()

const verificationCode = ref('')
const resendingCode = ref(false)

async function handleVerify() {
    if (verificationCode.value.length !== 6) return

    try {
        const response = await axios.post('/verify-email', {
            code: verificationCode.value
        })

        if (response.data.success) {
            showToast('Email verified successfully!', 'success')
            verificationCode.value = ''
            await checkAuth()
            emit('success')
            emit('update:modelValue', false)
        }
    } catch {
        showToast('Verification failed', 'error')
    }
}

async function resendCode() {
    resendingCode.value = true
    try {
        const response = await axios.post('/resend-verification')
        if (response.data.success) {
            showToast('Verification code resent!', 'success')
        }
    } catch {
        showToast('Failed to resend code', 'error')
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
                    <button
                        type="button"
                        class="btn-link change-email-link"
                        @click="$emit('changeEmail')"
                    >
                        Change email address
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
    max-width: 400px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    font-family: 'pixel art', monospace;
}

.modal-content h2 {
    margin: 0 0 20px 0;
    color: #1e1e1e;
    font-size: 24px;
    text-align: center;
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
    font-weight: 600;
    font-size: 13px;
}

.verification-input {
    width: 100%;
    padding: 14px 12px;
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    font-size: 24px;
    letter-spacing: 8px;
    text-align: center;
    font-weight: 600;
    font-family: 'pixel art', monospace;
    transition: all 0.2s;
    box-sizing: border-box;
    background: white;
}

.verification-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 20px;
}

.change-email-link {
    margin-top: 4px;
    font-size: 13px;
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

.btn-link {
    background: none;
    border: none;
    color: #2563eb;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    padding: 0;
    transition: color 0.2s;
}

.btn-link:hover:not(:disabled) {
    color: #1e40af;
}

.btn-link:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(0, 0, 0, 0.05);
    border: none;
    font-size: 28px;
    color: #666;
    cursor: pointer;
    line-height: 1;
    padding: 0;
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

@media (max-width: 640px) {
    .modal-content {
        width: 100%;
        height: 100%;
        max-height: 100%;
        border-radius: 0;
        display: flex;
        flex-direction: column;
        padding: 24px 20px;
        padding-top: 80px;
        padding-bottom: 60px;
    }

    .modal-close {
        top: 24px;
        right: 20px;
        width: 44px;
        height: 44px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }

    .modal-close:hover {
        background: rgba(0, 0, 0, 0.1);
    }

    .form-actions {
        margin-top: auto;
    }

    .btn-primary {
        border-radius: 12px;
    }
}
</style>
