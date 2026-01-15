<script setup>
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'switchToRegister', 'success'])

const { login, loading } = useAuth()
const { showToast } = useToast()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)

async function handleSubmit() {
    const result = await login(email.value, password.value, rememberMe.value)
    if (result.success) {
        showToast('Logged in successfully!', 'success')
        email.value = ''
        password.value = ''
        emit('success')
        emit('update:modelValue', false)
    } else {
        showToast(result.error || 'Login failed', 'error')
    }
}

function close() {
    emit('update:modelValue', false)
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <h2>Login</h2>
            <form @submit.prevent="handleSubmit">
                <div class="form-group">
                    <label>Email</label>
                    <input v-model="email" type="email" required autocomplete="email" />
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input v-model="password" type="password" required autocomplete="current-password" />
                </div>
                <label class="checkbox-group">
                    <input v-model="rememberMe" type="checkbox" />
                    Remember me
                </label>
                <div class="form-actions">
                    <button type="submit" class="btn-primary" :disabled="loading">
                        {{ loading ? 'Logging in...' : 'Login' }}
                    </button>
                    <button type="button" class="btn-link" @click="$emit('switchToRegister')">
                        Don't have an account? Register
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

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #ab9ff2;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    margin-bottom: 16px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
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

.btn-link:hover {
    color: #9d8df1;
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
