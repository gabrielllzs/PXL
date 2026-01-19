import { ref, onMounted } from 'vue'
import axios from 'axios'

const user = ref(null)
const group = ref(null)
const loading = ref(false)

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
            return { success: true }
        } catch {
            return { success: false, error: 'Login failed' }
        } finally {
            loading.value = false
        }
    }

    async function register(username, email, password, passwordConfirmation, country = null) {
        loading.value = true
        try {
            const response = await axios.post('/register', {
                username,
                email,
                password,
                password_confirmation: passwordConfirmation,
                country
            })

            if (response.data.csrf_token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', response.data.csrf_token)
            }

            await checkAuth()
            return { success: true }
        } catch (err) {
            if (err.response?.status === 422 && err.response?.data?.errors) {
                // Laravel validation errors
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
        return !!user.value?.email_verified_at
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

    return {
        user,
        group,
        loading,
        checkAuth,
        login,
        register,
        logout,
        fetchGroup,
        isAuthenticated: () => user.value !== null,
        isEmailVerified
    }
}
