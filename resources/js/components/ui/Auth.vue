<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import AuthModal from '@/components/auth/AuthModal.vue'
import VerifyEmailModal from '@/components/auth/VerifyEmailModal.vue'
import ForgotPasswordModal from '@/components/auth/ForgotPasswordModal.vue'
import EmailChangeModal from '@/components/auth/EmailChangeModal.vue'
import UserMenu from '@/components/auth/UserMenu.vue'

defineProps({
    hidden: {
        type: Boolean,
        default: false
    }
})

const { user, loading, checkAuth, isEmailVerified, checkInviteCodeOnLoad, processPendingInviteCode } = useAuth()
const { showToast } = useToast()

const showAuthModal = ref(false)
const authMode = ref('login')
const showVerifyModal = ref(false)
const showForgotPasswordModal = ref(false)
const showEmailChangeModal = ref(false)
const verificationEmail = ref('')
const resendingCode = ref(false)

const isAuthenticated = computed(() => user.value !== null)
const needsVerification = computed(() => isAuthenticated.value && !isEmailVerified())

function openLogin() {
    authMode.value = 'login'
    showAuthModal.value = true
}

function openRegister() {
    authMode.value = 'register'
    showAuthModal.value = true
}

function openVerify(email) {
    verificationEmail.value = email
    showVerifyModal.value = true
    showAuthModal.value = false
}

async function handleAuthSuccess() {
    showAuthModal.value = false
    const inviteResult = await processPendingInviteCode()
    if (inviteResult) {
        if (inviteResult.success) {
            showToast(inviteResult.message || 'Joined group successfully!', 'success')
        } else {
            showToast(inviteResult.error || 'Could not join group', 'error')
        }
    }
}

function handleAuthVerify(email) {
    openVerify(email)
}

async function showVerifyModalForUser() {
    await checkAuth()
    if (user.value?.email) {
        openVerify(user.value.email)
    }
}

async function handleVerifySuccess() {
    showVerifyModal.value = false
    // Check for pending invite code after email verification
    await checkAuth()
    const inviteResult = await processPendingInviteCode()
    if (inviteResult) {
        if (inviteResult.success) {
            showToast(inviteResult.message || 'Joined group successfully!', 'success')
        } else if (inviteResult.error) {
            showToast(inviteResult.error, 'error')
        }
    }
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
    isEmailVerified: () => !!user.value?.email_verified,
    openLogin
})

onMounted(async () => {
    await checkAuth()
    // Check for invite code in URL
    const inviteResult = await checkInviteCodeOnLoad()
    if (inviteResult) {
        if (inviteResult.needsAuth) {
            // Open login modal if not authenticated
            openLogin()
        } else if (inviteResult.success) {
            showToast(inviteResult.message || 'Joined group successfully!', 'success')
        } else if (inviteResult.error) {
            showToast(inviteResult.error || 'Could not join group', 'error')
        }
    }
})
</script>

<template>
    <div class="container" :class="{ 'mobile-hidden': hidden }">
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
            class="login-btn"
            :disabled="loading"
        >
            <span class="btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                </svg>
            </span>
            <span class="btn-label">{{ loading ? 'Loading...' : 'Login' }}</span>
        </button>

        <UserMenu v-else />

        <AuthModal
            v-model="showAuthModal"
            :initial-mode="authMode"
            @success="handleAuthSuccess"
            @verify="handleAuthVerify"
            @forgotPassword="showForgotPasswordModal = true"
        />

        <VerifyEmailModal
            v-model="showVerifyModal"
            :email="verificationEmail"
            @success="handleVerifySuccess"
            @changeEmail="showEmailChangeModal = true"
        />

        <ForgotPasswordModal
            v-model="showForgotPasswordModal"
            @success="showAuthModal = false"
        />

        <EmailChangeModal
            v-model="showEmailChangeModal"
            @success="showVerifyModal = false"
        />
    </div>
</template>

<style scoped>
.container {
    position: fixed;
    top: 16px;
    right: 16px;
    z-index: 3;
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

.login-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: auto;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    font-family: 'pixel art', monospace;
}

.login-btn:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    border-color: rgba(0, 0, 0, 0.12);
}

.login-btn:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
}

.login-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.login-btn .btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.login-btn:hover .btn-icon {
    background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
}

.login-btn .btn-icon svg {
    width: 20px;
    height: 20px;
}

.login-btn .btn-label {
    font-size: 14px;
    font-weight: 600;
    color: #1e1e1e;
    white-space: nowrap;
}

/* Responsive */
@media (max-width: 640px) {
    .container {
        top: 12px;
        right: 12px;
        gap: 8px;
    }

    .container.mobile-hidden {
        display: none !important;
    }

    .login-btn {
        padding: 10px 14px;
        border-radius: 12px;
    }

    .login-btn .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
    }

    .login-btn .btn-icon svg {
        width: 18px;
        height: 18px;
    }

    .login-btn .btn-label {
        font-size: 13px;
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
