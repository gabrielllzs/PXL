import { ref, onMounted } from 'vue'
import axios from 'axios'

const user = ref(null)
const loading = ref(false)

export function useAuth() {
    function getCsrfToken() {
        // Simply get CSRF token from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]')
        return metaTag?.getAttribute('content') || ''
    }

    async function checkAuth() {
        try {
            const response = await axios.get('/api/me', { withCredentials: true })
            if (response.data && (response.data.id || response.data.email)) {
                user.value = response.data
                return response.data
            } else {
                user.value = null
                return null
            }
        } catch (err) {
            // If error, definitely not authenticated
            user.value = null
            return null
        }
    }

    async function login(email, password, remember = false) {
        loading.value = true
        try {
            const csrfToken = getCsrfToken()
            
            const response = await axios.post('/login', {
                email,
                password,
                remember,
                _token: csrfToken
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                withCredentials: true
            })

            await checkAuth()
            return { success: true, user: response.data?.user }
        } catch (err) {
            // If CSRF mismatch (419), reload page to get fresh token
            if (err.response?.status === 419) {
                // Reload page to get fresh CSRF token from meta tag
                window.location.reload()
                return { success: false, error: 'CSRF token expired. Please try again.' }
            }
            const error = err.response?.data?.error || err.response?.data?.message || 'Login failed'
            return { success: false, error }
        } finally {
            loading.value = false
        }
    }

    async function register(name, username, email, password, passwordConfirmation, country = null) {
        loading.value = true
        try {
            const csrfToken = getCsrfToken()
            
            const response = await axios.post('/register', {
                name,
                username,
                email,
                password,
                password_confirmation: passwordConfirmation,
                country,
                _token: csrfToken
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                withCredentials: true
            })

            // Refresh auth state
            await checkAuth()
            return { success: true, user: response.data?.user }
        } catch (err) {
            // If CSRF mismatch (419), reload page to get fresh token
            if (err.response?.status === 419) {
                window.location.reload()
                return { success: false, error: 'CSRF token expired. Please try again.' }
            }
            const errors = err.response?.data?.errors || {}
            const error = err.response?.data?.error || err.response?.data?.message || 'Registration failed'
            return { success: false, error, errors }
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        loading.value = true
        try {
            const csrfToken = getCsrfToken()
            
            await axios.post('/logout', {
                _token: csrfToken
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                withCredentials: true
            })
            
            user.value = null
            
            // After logout, reload page to get fresh CSRF token in meta tag
            // The server regenerated the token, so we need to reload to get the new one
            window.location.reload()
            
            return { success: true }
        } catch (err) {
            // Even if logout fails, clear local state and reload
            user.value = null
            window.location.reload()
            return { success: false, error: err.message }
        } finally {
            loading.value = false
        }
    }

    // Check auth on mount
    onMounted(() => {
        checkAuth()
    })

    function isEmailVerified() {
        if (!user.value) return false
        return !!user.value.email_verified_at
    }

    return {
        user,
        loading,
        checkAuth,
        login,
        register,
        logout,
        isAuthenticated: () => user.value !== null,
        isEmailVerified
    }
}
