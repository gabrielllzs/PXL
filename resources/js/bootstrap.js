import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common.Accept = 'application/json'

// Set CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken
}
