import { reactive } from 'vue'
import axios from 'axios'
import FingerprintJS from '@fingerprintjs/fingerprintjs'
import { useAuth } from './useAuth'

let visitorId = null
let fpComponents = null

export const stored = reactive([])
const cooldown = reactive({ active: false, remaining: 0 })
let cooldownTimer = null

async function initFingerprint() {
    if (!visitorId || !fpComponents) {
        const fp = await FingerprintJS.load()
        const result = await fp.get()
        visitorId = result.visitorId
        fpComponents = result.components
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const data = {
                x,
                y,
                color,
                visitorId,
                captchaToken,
            }

            const response = await axios.post('/api/pixel', data, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })

            const existing = stored.find(p => p.x === x && p.y === y)
            if (existing) {
                existing.color = color
                if (response.data?.id) existing.id = response.data.id
            } else {
                stored.push({ x, y, color, id: response.data?.id })
            }

            // Cooldown duration will be determined by server based on auth status
            // Only start cooldown for non-authenticated users
            const { isAuthenticated } = useAuth()
            if (!isAuthenticated()) {
                const cooldownDuration = response.data?.cooldownDuration || 10
                startCooldown(cooldownDuration)
            }
            return true
        } catch (err) {
            if (err.response?.status === 419) {
                // CSRF token expired - reload page to get fresh token from meta tag
                // This typically happens after login when session is regenerated
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
        await initFingerprint()
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const res = await axios.get('/api/cooldown', {
                params: { visitorId },
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            const remaining = res.data.remaining || 0
            if (remaining > 0) startCooldown(remaining)
        } catch (err) {
            console.error('Failed to sync cooldown (likely server or network issue):', err);
        }
    }

    return { stored, load, save, cooldown, syncCooldown }
}
