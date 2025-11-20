import { reactive } from 'vue'
import axios from 'axios'
import FingerprintJS from '@fingerprintjs/fingerprintjs'

let visitorId = null

const stored = reactive([])
const cooldown = reactive({ active: false, remaining: 0 })
let cooldownTimer = null

async function initFingerprint() {
    if (!visitorId) {
        const fp = await FingerprintJS.load()
        const result = await fp.get()
        visitorId = result.visitorId
    }
}

function startCooldown(seconds) {
    const s = Number.isFinite(seconds) ? Math.max(0, Math.round(seconds)) : 60
    cooldown.active = s > 0
    cooldown.remaining = s

    if (cooldownTimer) clearInterval(cooldownTimer)

    if (!cooldown.active) return

    cooldownTimer = setInterval(() => {
        cooldown.remaining -= 1
        if (cooldown.remaining <= 0) {
            cooldown.active = false
            cooldown.remaining = 0
            clearInterval(cooldownTimer)
            cooldownTimer = null
        }
    }, 1000)
}

export function usePixels() {
    async function load() {
        await initFingerprint()
        try {
            const res = await axios.get('/api/pixel', { params: { visitorId } })
            stored.splice(0, stored.length, ...res.data)
        } catch (err) {
            console.error('Failed to load pixels:', err)
        }
    }

    async function save(x, y, color) {
        await initFingerprint()

        if (cooldown.active) return false

        try {
            const existing = stored.find(p => p.x === x && p.y === y)
            if (existing) {
                existing.color = color
                if (existing.id) {
                    await axios.put(`/api/pixel/${existing.id}`, { color, visitorId })
                } else {
                    const res = await axios.post('/api/pixel', { x, y, color, visitorId })
                    if (res.data?.id) existing.id = res.data.id
                }
            } else {
                const newCell = { x, y, color }
                stored.push(newCell)
                const res = await axios.post('/api/pixel', { ...newCell, visitorId })
                if (res.data?.id) newCell.id = res.data.id
            }

            startCooldown(60)
            return true
        } catch (err) {
            if (err.response?.status === 429) {
                const remaining = err.response.data?.remaining ?? 60
                startCooldown(remaining)
                return false
            } else {
                console.error('Unexpected error:', err)
                return false
            }
        }
    }

    async function syncCooldown() {
        await initFingerprint()
        const res = await axios.get('/api/cooldown', { params: { visitorId } })
        const remaining = res.data.remaining || 0
        if (remaining > 0) startCooldown(remaining)
    }

    return { stored, load, save, cooldown, syncCooldown }
}
