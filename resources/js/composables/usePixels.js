import { reactive } from 'vue'
import axios from 'axios'
import FingerprintJS from '@fingerprintjs/fingerprintjs'
import { useAuth } from './useAuth'

let visitorId = null

export const stored = reactive([])
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
        try {
            const response = await axios.get('/api/map-data')
            stored.splice(0, stored.length, ...response.data)
        } catch (err) {
            console.error('Failed to load pixels:', err)
        }
    }

    async function save(x, y, color, captchaToken) {
        await initFingerprint()

        // Skip cooldown check for authenticated users
        const { isAuthenticated } = useAuth()
        if (!isAuthenticated() && cooldown.active) return false

        try {
            const data = {
                x,
                y,
                color,
                visitorId,
                captchaToken,
            }

            const response = await axios.post('/api/pixel', data)

            const existing = stored.find(p => p.x === x && p.y === y)
            if (existing) {
                existing.color = color
                if (response.data?.id) existing.id = response.data.id
            } else {
                stored.push({ x, y, color, id: response.data?.id })
            }

            // Only start cooldown for non-authenticated users
            const { isAuthenticated } = useAuth()
            if (!isAuthenticated()) {
                const cooldownDuration = response.data?.cooldownDuration || 10
                startCooldown(cooldownDuration)
            }
            return true
        } catch (err) {
            if (err.response?.status === 419) {
                // CSRF token expired - reload page to get fresh session
                console.error('CSRF token expired, reloading page')
                window.location.reload()
                return false
            } else if (err.response?.status === 412) {
                const remaining = err.response.data?.remaining ?? 1
                startCooldown(remaining)
                return false
            } else if (err.response?.status === 403) {
                const error = err.response.data?.error
                if (error === 'email_not_verified') {
                    // Return a special error code so the UI can handle it
                    throw { type: 'email_not_verified', message: err.response.data?.message || 'Please verify your email' }
                }
                console.error('Security Blocked');
                return false;
            } else {
                console.error('Unexpected error:', err)
                return false
            }
        }
    }

    async function syncCooldown() {
        const { isAuthenticated } = useAuth()
        // Authenticated users have no cooldown, skip sync
        if (isAuthenticated()) return
        
        await initFingerprint()
        try {
            const res = await axios.get('/api/cooldown', {
                params: { visitorId }
            })
            const remaining = res.data.remaining || 0
            if (remaining > 0) startCooldown(remaining)
        } catch (err) {
            console.error('Failed to sync cooldown (likely server or network issue):', err);
        }
    }

    return { stored, load, save, cooldown, syncCooldown }
}
