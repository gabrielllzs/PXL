import { ref } from 'vue'
import axios from 'axios'
import { useAuth } from './useAuth'
import { useToast } from './useToast'

const locations = ref([])
const loading = ref(false)

export function useSavedLocations() {
    const { isAuthenticated } = useAuth()
    const { showToast } = useToast()

    async function load() {
        if (!isAuthenticated()) {
            locations.value = []
            return
        }

        loading.value = true
        try {
            const { data } = await axios.get('/saved-locations', {
                withCredentials: true
            })
            locations.value = data
        } catch (err) {
            console.error('Failed to load saved locations:', err)
            if (err.response?.status !== 401) {
                showToast('Failed to load saved locations', 'error')
            }
        } finally {
            loading.value = false
        }
    }

    async function save(name, longitude, latitude, zoom) {
        if (!isAuthenticated()) {
            showToast('Please sign in to save locations', 'error')
            return null
        }

        try {
            const { data } = await axios.post('/saved-locations', {
                name: name || null,
                longitude,
                latitude,
                zoom: zoom || null
            }, {
                withCredentials: true
            })
            
            locations.value.unshift(data)
            showToast('Location saved!', 'success')
            return data
        } catch (err) {
            const status = err.response?.status
            const data = err.response?.data

            if (status === 400 && data?.error === 'You can only save up to 3 locations') {
                showToast('You can only save up to 3 locations', 'error')
            } else if (status === 401) {
                showToast('Please sign in to save locations', 'error')
            } else {
                showToast('Failed to save location', 'error')
            }
            return null
        }
    }

    async function remove(id) {
        if (!isAuthenticated()) return false

        try {
            await axios.delete(`/saved-locations/${id}`, {
                withCredentials: true
            })
            
            locations.value = locations.value.filter(loc => loc.id !== id)
            showToast('Location deleted', 'success')
            return true
        } catch (err) {
            if (err.response?.status === 404) {
                // Already deleted, remove from local state
                locations.value = locations.value.filter(loc => loc.id !== id)
            } else {
                showToast('Failed to delete location', 'error')
            }
            return false
        }
    }

    async function loadByKey(key) {
        try {
            const { data } = await axios.get(`/location/${key}`)
            return data
        } catch (err) {
            if (err.response?.status === 404) {
                showToast('Location not found', 'error')
            } else {
                showToast('Failed to load location', 'error')
            }
            return null
        }
    }

    function getShareUrl(location) {
        if (!location?.share_key) return null
        return `${window.location.origin}?location=${location.share_key}`
    }

    return {
        locations,
        loading,
        load,
        save,
        remove,
        loadByKey,
        getShareUrl
    }
}
