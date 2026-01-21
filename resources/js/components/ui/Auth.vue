<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import LoginModal from '@/components/auth/LoginModal.vue'
import RegisterModal from '@/components/auth/RegisterModal.vue'
import VerifyEmailModal from '@/components/auth/VerifyEmailModal.vue'
import UserMenu from '@/components/auth/UserMenu.vue'

const { user, loading, checkAuth, isEmailVerified } = useAuth()
const { showToast } = useToast()

const showLoginModal = ref(false)
const showRegisterModal = ref(false)
const showVerifyModal = ref(false)
const verificationEmail = ref('')
const resendingCode = ref(false)

const isAuthenticated = computed(() => user.value !== null)
const needsVerification = computed(() => isAuthenticated.value && !isEmailVerified())

function openLogin() {
    showLoginModal.value = true
    showRegisterModal.value = false
}

function openRegister() {
    showRegisterModal.value = true
    showLoginModal.value = false
}

function openVerify(email) {
    verificationEmail.value = email
    showVerifyModal.value = true
    showRegisterModal.value = false
}

function handleLoginSuccess() {
    showLoginModal.value = false
}

function handleRegisterVerify(email) {
    openVerify(email)
}

// Expose function to open verify modal from outside
async function showVerifyModalForUser() {
    // Refresh auth state first to get latest user data
    await checkAuth()
    if (user.value?.email) {
        openVerify(user.value.email)
    }
}

function handleVerifySuccess() {
    showVerifyModal.value = false
    // Auth state will be refreshed by VerifyEmailModal
}

async function resendVerificationCode() {
    if (!user.value?.email) return

    resendingCode.value = true
    try {
        const response = await axios.post('/resend-verification', {}, {
            withCredentials: true
        })

        if (response.data.success) {
            showToast('Verification code resent! Check your email.', 'success')
            // Open verification modal with the email
            openVerify(user.value.email)
        } else {
            showToast(response.data.error || 'Failed to resend code', 'error')
        }
    } catch {
        showToast('Failed to resend code', 'error')
    } finally {
        resendingCode.value = false
    }
}

defineExpose({
    showVerifyModal: showVerifyModalForUser,
    isEmailVerified: () => user.value?.email_verified_at !== null
})

onMounted(checkAuth)
</script>

<template>
    <div class="container">
        <div v-if="needsVerification" class="verification-banner">
            <div class="banner-content">
                <span class="banner-text">Please verify your email to place pixels</span>
                <button
                    @click="resendVerificationCode"
                    class="banner-btn"
                    :disabled="resendingCode"
                >
                    {{ resendingCode ? 'Sending...' : 'Resend Code' }}
                </button>
            </div>
        </div>

        <button
            v-if="!isAuthenticated"
            @click="openLogin"
            class="auth-btn connect-btn"
            :disabled="loading"
        >
            {{ loading ? 'Loading...' : 'Login / Register' }}
        </button>

        <UserMenu v-else />

        <LoginModal
            v-model="showLoginModal"
            @switch-to-register="openRegister"
            @success="handleLoginSuccess"
        />

        <RegisterModal
            v-model="showRegisterModal"
            @switch-to-login="openLogin"
            @verify="handleRegisterVerify"
        />

        <VerifyEmailModal
            v-model="showVerifyModal"
            :email="verificationEmail"
            @success="handleVerifySuccess"
        />
    </div>
</template>

<style scoped>
.container {
    position: fixed;
    top: 16px;
    right: 16px;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.verification-banner {
    position: fixed;
    top: 16px;
    left: 50%;
    transform: translateX(-50%);
    background: #f59e0b;
    border: 2px solid rgba(217, 119, 6, 0.3);
    border-radius: 12px;
    padding: 12px 16px;
    pointer-events: auto;
    min-width: 300px;
    max-width: 500px;
    z-index: 2;
}

.banner-content {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

.banner-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.banner-text {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
}

.banner-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
}

.banner-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-1px);
}

.banner-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.auth-btn {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    pointer-events: auto;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    font-family: inherit;
    font-weight: 500;
}

.auth-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.1);
}

.auth-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.connect-btn {
    background: rgb(56, 191, 63);
    border-color: rgb(122, 191, 127, 0.3);
    color: white;
    min-width: 160px;
    justify-content: center;
}

.connect-btn:hover:not(:disabled) {
    border-color: rgb(122, 191, 127);
}

/* Responsive */
@media (max-width: 640px) {
    .container {
        top: 12px;
        right: 12px;
        gap: 8px;
    }

    .auth-btn {
        padding: 10px 12px;
        border-radius: 10px;
    }

    .connect-btn {
        min-width: 140px;
    }

    .verification-banner {
        min-width: 280px;
        max-width: calc(100vw - 24px);
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
    }

    .banner-content {
        flex-wrap: wrap;
        gap: 8px;
    }

    .banner-text {
        font-size: 13px;
        width: 100%;
    }

    .banner-btn {
        width: 100%;
        padding: 8px 12px;
    }
}
</style>
