import axios from 'axios'

axios.defaults.baseURL = import.meta.env.VITE_APP_URL || 'pixel-art.design'
axios.defaults.withCredentials = true
axios.defaults.headers.common.Accept = 'application/json'

// Set CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken
}
