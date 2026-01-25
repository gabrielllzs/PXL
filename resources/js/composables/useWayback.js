import { ref } from 'vue'
import axios from 'axios'

const waybackHistory = ref([])

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
        if (!waybackHistory.value.length) return []

        // Filter entries where created_at <= targetDate
        const filtered = waybackHistory.value.filter(entry => {
            const entryDate = new Date(entry.created_at)
            return entryDate <= targetDate
        })

        // Group by x,y and get the most recent for each coordinate
        const pixelMap = new Map()
        
        filtered.forEach(entry => {
            const key = `${entry.x},${entry.y}`
            const existing = pixelMap.get(key)
            
            if (!existing || new Date(entry.created_at) > new Date(existing.created_at)) {
                pixelMap.set(key, entry)
            }
        })

        // Convert map back to array
        return Array.from(pixelMap.values())
    }

    return {
        waybackHistory,
        loadWayback,
        filterByDate
    }
}
