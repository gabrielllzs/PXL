import { ref } from 'vue'
import axios from 'axios'

const waybackHistory = ref([])
const isActive = ref(false)
const filteredPixels = ref([])

export function useWayback() {
    async function loadWayback() {
        try {
            const { data } = await axios.get('/api/wayback')
            waybackHistory.value = data
        } catch (err) {
            console.error('Failed to load wayback history:', err)
        }
    }

    function filterByDate(targetDate) {
        if (!waybackHistory.value.length) {
            filteredPixels.value = []
            return []
        }

        // Filter entries where created_at <= targetDate
        const filtered = waybackHistory.value.filter(entry => {
            const entryDate = new Date(entry.created_at)
            return entryDate <= targetDate
        })
        
        filteredPixels.value = filtered
        return filtered
    }

    function setActive(active) {
        isActive.value = active
        if (!active) {
            filteredPixels.value = []
        }
    }

    return {
        waybackHistory,
        isActive,
        filteredPixels,
        loadWayback,
        filterByDate,
        setActive
    }
}
