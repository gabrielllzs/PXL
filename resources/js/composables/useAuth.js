import { ref, onMounted } from 'vue'
import axios from 'axios'

const user = ref(null)
const loading = ref(false)

export function useAuth() {

    async function checkAuth() {
        try {
            const response = await axios.get('/api/me')
            if (response.data && (response.data.id || response.data.email)) {
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
            await axios.post('/login', { email, password, remember })
            await checkAuth()
            window.location.reload()
            return { success: true }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || err.response?.data?.error || 'Login failed' }
        } finally {
            loading.value = false
        }
    }

    async function register(username, email, password, passwordConfirmation, country = null) {
        loading.value = true
        try {
            await axios.post('/register', {
                username,
                email,
                password,
                password_confirmation: passwordConfirmation,
                country
            })
            await checkAuth()
            return { success: true }
        } catch (err) {
            return { 
                success: false, 
                error: err.response?.data?.message || err.response?.data?.error || 'Registration failed',
                errors: err.response?.data?.errors || {}
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

    onMounted(async () => {
        await checkAuth()
    })

    function isEmailVerified() {
        return !!user.value?.email_verified_at
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
