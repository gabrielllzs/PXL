import { ref, onMounted } from 'vue'
import axios from 'axios'
import FingerprintJS from '@fingerprintjs/fingerprintjs'

const user = ref(null)
const group = ref(null)
const loading = ref(false)
let visitorId = null

async function initFingerprint() {
    if (visitorId) return visitorId
    try {
        const fp = await FingerprintJS.load()
        const result = await fp.get()
        visitorId = result.visitorId
        return visitorId
    } catch (err) {
        console.error('Failed to initialize fingerprint:', err)
        return null
    }
}

export function useAuth() {

    async function checkAuth() {
        try {
            const response = await axios.get('/api/me')
            if (response.data) {
                user.value = response.data
                return response.data
            }
            user.value = null
            return null
        } catch {
            user.value = null
            return null
        }
    }

    async function login(email, password, remember = false) {
        loading.value = true
        try {
            const response = await axios.post('/login', { email, password, remember })

            if (response.data.csrf_token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', response.data.csrf_token)
            }

            await checkAuth()
            const inviteResult = await processPendingInviteCode()
            return { success: true, inviteResult }
        } catch {
            return { success: false, error: 'Login failed' }
        } finally {
            loading.value = false
        }
    }

    async function register(username, email, password, passwordConfirmation, country = null) {
        loading.value = true
        try {
            // Initialize fingerprint to get visitorId
            const currentVisitorId = await initFingerprint()
            
            const response = await axios.post('/register', {
                username,
                email,
                password,
                password_confirmation: passwordConfirmation,
                country,
                visitorId: currentVisitorId
            })

            if (response.data.csrf_token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', response.data.csrf_token)
            }

            await checkAuth()
            const inviteResult = await processPendingInviteCode()
            return { success: true, inviteResult }
        } catch (err) {
            if (err.response?.status === 422 && err.response?.data?.errors) {
                const errors = err.response.data.errors
                const errorMessages = []

                if (errors.username) {
                    errorMessages.push('Username already exists')
                }
                if (errors.email) {
                    errorMessages.push('Email already exists')
                }
                if (errors.password) {
                    errorMessages.push(errors.password[0] || 'Invalid password')
                }

                return {
                    success: false,
                    error: errorMessages.length > 0 ? errorMessages.join(', ') : 'Validation failed',
                    errors: errors
                }
            }
            return {
                success: false,
                error: err.response?.data?.message || 'Registration failed'
            }
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        loading.value = true
        try {
            await axios.post('/logout')
            user.value = null
            window.location.reload()
        } catch {
            user.value = null
            window.location.reload()
        } finally {
            loading.value = false
        }
    }

    function isEmailVerified() {
        return !!user.value?.email_verified
    }

    async function fetchGroup(){
        loading.value = true
        try {
            const response = await axios.get('/group');
            if (response.data) {
                group.value = response.data;
                return response.data
            }
            group.value = null;
            return null;
        } catch (err) {
            if (err.response?.status === 404) {
                group.value = null
                return null
            }
            // Other
            group.value = null
            return null
        } finally {
            loading.value = false
        }
    }

    async function joinGroupByInviteCode(inviteCode) {
        if (!inviteCode || !user.value) return { success: false, error: 'Not authenticated' }
        if (group.value) return { success: false, error: 'Already in a group' }

        loading.value = true
        try {
            const response = await axios.post('/group/join', {
                invite_code: inviteCode
            })

            if (response.data) {
                await fetchGroup()
                // Remove invite code from URL
                const url = new URL(window.location.href)
                url.searchParams.delete('invite')
                window.history.replaceState({}, '', url.toString())
                return { success: true, message: response.data.message || 'Joined group successfully' }
            }
            return { success: false, error: 'Failed to join group' }
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Could not join group'
            return { success: false, error: errorMsg }
        } finally {
            loading.value = false
        }
    }

    function getInviteCodeFromUrl() {
        const urlParams = new URLSearchParams(window.location.search)
        return urlParams.get('invite')
    }

    function storeInviteCode(inviteCode) {
        if (inviteCode) {
            sessionStorage.setItem('pending_invite_code', inviteCode)
        }
    }

    function getStoredInviteCode() {
        return sessionStorage.getItem('pending_invite_code')
    }

    function clearStoredInviteCode() {
        sessionStorage.removeItem('pending_invite_code')
    }

    async function checkInviteCodeOnLoad() {
        const urlParams = new URLSearchParams(window.location.search)
        const inviteCode = urlParams.get('invite')

        // Store invite code if present in URL
        if (inviteCode) {
            storeInviteCode(inviteCode)
            // Remove from URL to keep it clean
            const url = new URL(window.location.href)
            url.searchParams.delete('invite')
            window.history.replaceState({}, '', url.toString())
        }

        // If authenticated, try to join
        if (user.value) {
            const codeToUse = inviteCode || getStoredInviteCode()
            if (codeToUse && !group.value) {
                const result = await joinGroupByInviteCode(codeToUse)
                if (result?.success) {
                    clearStoredInviteCode()
                }
                return result
            }
        }

        // Return invite code if not authenticated (so UI can open login)
        if (inviteCode || getStoredInviteCode()) {
            return { needsAuth: true, inviteCode: inviteCode || getStoredInviteCode() }
        }

        return null
    }

    async function processPendingInviteCode() {
        const storedCode = getStoredInviteCode()
        if (storedCode && user.value && !group.value) {
            const result = await joinGroupByInviteCode(storedCode)
            if (result?.success) {
                clearStoredInviteCode()
            }
            return result
        }
        return null
    }

    return {
        user,
        group,
        loading,
        checkAuth,
        login,
        register,
        logout,
        fetchGroup,
        joinGroupByInviteCode,
        checkInviteCodeOnLoad,
        processPendingInviteCode,
        getStoredInviteCode,
        isAuthenticated: () => user.value !== null,
        isEmailVerified
    }
}
