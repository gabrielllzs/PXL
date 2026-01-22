<script setup>
import { ref, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    initialMode: {
        type: String,
        default: 'login' // 'login' or 'register'
    }
})

const emit = defineEmits(['update:modelValue', 'success', 'verify'])

const { login, register, loading } = useAuth()
const { showToast } = useToast()

const mode = ref(props.initialMode)

// Form fields
const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const username = ref('')
const passwordConfirmation = ref('')

// Reset form when modal opens/closes or mode changes
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        mode.value = props.initialMode
    }
    resetForm()
})

watch(mode, () => {
    resetForm()
})

function resetForm() {
    email.value = ''
    password.value = ''
    rememberMe.value = false
    username.value = ''
    passwordConfirmation.value = ''
}

async function handleLogin() {
    const result = await login(email.value, password.value, rememberMe.value)
    if (result.success) {
        showToast('Logged in successfully!', 'success')
        emit('success')
        emit('update:modelValue', false)
    } else {
        showToast(result.error || 'Login failed', 'error')
    }
}

async function handleRegister() {
    const result = await register(
        username.value,
        email.value,
        password.value,
        passwordConfirmation.value,
        null
    )
    if (result.success) {
        showToast('Account created! Please verify your email.', 'success')
        emit('verify', email.value)
        emit('update:modelValue', false)
    } else {
        const errorMsg = result.errors ? Object.values(result.errors).flat().join(', ') : result.error
        showToast(errorMsg || 'Registration failed', 'error')
    }
}

function handleSubmit() {
    if (mode.value === 'login') {
        handleLogin()
    } else {
        handleRegister()
    }
}

function switchMode() {
    mode.value = mode.value === 'login' ? 'register' : 'login'
}

function close() {
    emit('update:modelValue', false)
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" :class="mode" @click.stop>
            <button class="modal-close" @click="close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            
            <div class="modal-header">
                <div class="header-icon" :class="mode">
                    <!-- Login icon -->
                    <svg v-if="mode === 'login'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                    </svg>
                    <!-- Register icon -->
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z"/>
                    </svg>
                </div>
                <h2>{{ mode === 'login' ? 'Welcome back' : 'Create account' }}</h2>
                <p class="header-subtitle">
                    {{ mode === 'login' ? 'Sign in to continue placing pixels' : 'Join the community and start placing pixels' }}
                </p>
            </div>
            
            <form @submit.prevent="handleSubmit" class="auth-form">
                <!-- Username (Register only) -->
                <div v-if="mode === 'register'" class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="input-icon">
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd"/>
                        </svg>
                        <input
                            id="username"
                            v-model="username"
                            type="text"
                            required
                            autocomplete="username"
                            placeholder="Choose a username"
                        />
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="input-icon">
                            <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                            <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
                        </svg>
                        <input 
                            id="email"
                            v-model="email" 
                            type="email" 
                            required 
                            autocomplete="email"
                            placeholder="you@example.com"
                        />
                    </div>
                </div>
                
                <!-- Password -->
                <div :class="mode === 'register' ? 'form-row' : ''">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="input-icon">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/>
                            </svg>
                            <input 
                                id="password"
                                v-model="password" 
                                type="password" 
                                required 
                                :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
                                :placeholder="mode === 'login' ? 'Enter your password' : 'Create password'"
                            />
                        </div>
                    </div>
                    
                    <!-- Confirm Password (Register only) -->
                    <div v-if="mode === 'register'" class="form-group">
                        <label for="password-confirm">Confirm</label>
                        <div class="input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="input-icon">
                                <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                            </svg>
                            <input
                                id="password-confirm"
                                v-model="passwordConfirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />
                        </div>
                    </div>
                </div>
                
                <!-- Remember me (Login only) -->
                <label v-if="mode === 'login'" class="checkbox-group">
                    <input v-model="rememberMe" type="checkbox" />
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-label">Remember me</span>
                </label>
                
                <button type="submit" class="btn-primary" :class="mode" :disabled="loading">
                    <span v-if="!loading">{{ mode === 'login' ? 'Sign in' : 'Create account' }}</span>
                    <span v-else class="loading-state">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="31.4 31.4" />
                        </svg>
                        {{ mode === 'login' ? 'Signing in...' : 'Creating...' }}
                    </span>
                </button>
            </form>
            
            <div class="modal-footer">
                <span class="footer-text">
                    {{ mode === 'login' ? "Don't have an account?" : "Already have an account?" }}
                </span>
                <button type="button" class="btn-link" :class="mode" @click="switchMode">
                    {{ mode === 'login' ? 'Create one' : 'Sign in' }}
                </button>
            </div>
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

.modal-content.register {
    max-width: 440px;
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

.modal-content.login .modal-header {
    margin-bottom: 28px;
}

.header-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    color: white;
    transition: background 0.3s;
}

.header-icon.login {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.header-icon.register {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.header-icon svg {
    width: 28px;
    height: 28px;
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

.modal-content.login .auth-form {
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.modal-content.login .form-group {
    gap: 8px;
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

.modal-content.login .form-group input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-content.register .form-group input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}

.checkbox-group input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(0, 0, 0, 0.15);
    border-radius: 6px;
    background: white;
    position: relative;
    transition: all 0.2s;
    flex-shrink: 0;
}

.checkbox-group input:checked + .checkbox-custom {
    background: #2563eb;
    border-color: #2563eb;
}

.checkbox-group input:checked + .checkbox-custom::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 2px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'pixel art', monospace;
    font-weight: 600;
    font-size: 15px;
    margin-top: 4px;
}

.btn-primary.login {
    border: 2px solid rgba(59, 130, 246, 0.3);
    box-shadow: 0 2px 12px rgba(59, 130, 246, 0.15);
    color: #2563eb;
}

.btn-primary.login:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
    border-color: rgba(59, 130, 246, 0.5);
    background: rgba(239, 246, 255, 0.95);
}

.btn-primary.register {
    border: 2px solid rgba(16, 185, 129, 0.3);
    box-shadow: 0 2px 12px rgba(16, 185, 129, 0.15);
    color: #059669;
    margin-top: 8px;
}

.btn-primary.register:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.25);
    border-color: rgba(16, 185, 129, 0.5);
    background: rgba(236, 253, 245, 0.95);
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

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.modal-content.login .modal-footer {
    margin-top: 24px;
}

.footer-text {
    color: #888;
    font-size: 14px;
}

.btn-link {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    padding: 0;
    transition: color 0.2s;
}

.btn-link.login {
    color: #2563eb;
}

.btn-link.login:hover {
    color: #1e40af;
}

.btn-link.register {
    color: #10b981;
}

.btn-link.register:hover {
    color: #059669;
}

@media (max-width: 640px) {
    .modal-content {
        width: 100%;
        height: 100%;
        border-radius: 0;
        display: flex;
        flex-direction: column;
        padding: 24px 20px;
        padding-top: 80px;
        padding-bottom: 60px;
    }

    .modal-close {
        top: 40px;
        right: 20px;
        width: 44px;
        height: 44px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }

    .modal-close:hover {
        background: rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        margin-bottom: 20px;
    }

    .header-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
    }

    .header-icon svg {
        width: 24px;
        height: 24px;
    }

    .modal-header h2 {
        font-size: 22px;
    }

    .auth-form {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .btn-primary {
        margin-top: auto;
        border-radius: 12px;
    }

    .modal-footer {
        margin-top: 24px;
        padding-top: 24px;
        padding-bottom: 20px;
    }
}
</style>
