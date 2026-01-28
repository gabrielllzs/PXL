import { reactive, ref } from 'vue'
import axios from 'axios'
import FingerprintJS from '@fingerprintjs/fingerprintjs'
import { useAuth } from './useAuth'
import { useToast } from './useToast'
import { loadAll, addOrUpdate, getVisible, evictOutside } from './usePixelChunks'

let visitorId = null

const cooldown = reactive({ active: false, remaining: 0 })
let cooldownTimer = null

async function initFingerprint() {
    if (visitorId) return
    const fp = await FingerprintJS.load()
    const result = await fp.get()
    visitorId = result.visitorId
}

function startCooldown(seconds) {
    const s = Number.isFinite(seconds) ? Math.max(0, Math.round(seconds)) : 60
    cooldown.active = s > 0
    cooldown.remaining = s
    
    if (cooldownTimer) clearInterval(cooldownTimer)
    if (!cooldown.active) return
    
    cooldownTimer = setInterval(() => {
        cooldown.remaining--
        if (cooldown.remaining <= 0) {
            cooldown.active = false
            cooldown.remaining = 0
            clearInterval(cooldownTimer)
            cooldownTimer = null
        }
    }, 1000)
}

export const refetchViewportTrigger = ref(0)

export function usePixels() {
    const { isAuthenticated } = useAuth()
    const { showToast } = useToast()

    async function loadViewport(minX, maxX, minY, maxY) {
        try {
            const { data } = await axios.get('/api/map-data', {
                params: { minX, maxX, minY, maxY }
            })
            const arr = Array.isArray(data) ? data : []
            for (const p of arr) addOrUpdate(p.x, p.y, p.color ?? '#000000')
        } catch (err) {
            console.error('Failed to load viewport pixels:', err)
        }
    }

    function load() {
        refetchViewportTrigger.value++
    }

    async function save(x, y, color, captchaToken) {
        await initFingerprint()
        
        if (!isAuthenticated() && cooldown.active) return false
        
        try {
            const response = await axios.post('/api/pixel', { x, y, color, visitorId, captchaToken })
            
            addOrUpdate(x, y, color)
            
            if (!isAuthenticated()) {
                startCooldown(response.data?.cooldownDuration || 10)
            } else if (response.data?.leveled_up) {
                showToast(`Level Up! You're now Level ${response.data.level}! 🎉`, 'success')
            }
            
            return {
                success: true,
                pixels_available: response.data?.pixels_available,
                pixel_limit: response.data?.pixel_limit,
                level: response.data?.level,
                leveled_up: response.data?.leveled_up,
            }
        } catch (err) {
            const status = err.response?.status
            const data = err.response?.data
            
            if (status === 419) {
                window.location.reload()
            } else if (status === 412) {
                startCooldown(data?.remaining ?? 1)
            } else if (status === 429 && data?.error === 'no_pixels_available') {
                const msg = data?.time_until_next
                    ? `No pixels available. Next pixel in ${Math.ceil(data.time_until_next)}s`
                    : 'No pixels available. Please wait for them to regenerate.'
                showToast(msg, 'error')
                throw { type: 'no_pixels_available', message: msg }
            } else if (status === 429 && data?.error === 'ip_rate_limited') {
                const msg = data?.message || 'You haev been placing pixels too quickly. And have been rate limited.'
                const retrySec = data?.retry_after
                if (Number.isFinite(retrySec) && retrySec > 0) startCooldown(retrySec)
                showToast(msg, 'error')
                throw { type: 'ip_rate_limited', message: msg }
            } else if (status === 403 && data?.error === 'email_not_verified') {
                throw { type: 'email_not_verified', message: data?.message || 'Please verify your email' }
            } else if (status === 403 && data?.error === 'banned') {
                let message = data?.reason || 'You have been banned from placing pixels.'
                if (data?.is_permanent) {
                    message += ' (Permanent ban)'
                } else if (data?.banned_until) {
                    const expiryDate = new Date(data.banned_until)
                    message += ` (Ban expires: ${expiryDate.toLocaleString()})`
                }
                showToast(message, 'error')
                throw { type: 'banned', message, reason: data?.reason, is_permanent: data?.is_permanent, banned_until: data?.banned_until }
            } else if (status === 403 && data?.error === 'custom_color_requires_auth') {
                showToast(data?.message || 'Sign in to use custom colors.', 'error')
                throw { type: 'custom_color_requires_auth', message: data?.message }
            } else {
                console.error('Unexpected error:', err)
            }
            return false
        }
    }

    async function syncCooldown() {
        if (isAuthenticated()) return
        
        await initFingerprint()
        try {
            const { data } = await axios.get('/api/cooldown', { params: { visitorId } })
            if (data.remaining > 0) startCooldown(data.remaining)
        } catch (err) {
            console.error('Failed to sync cooldown:', err)
        }
    }

    return {
        load,
        loadViewport,
        save,
        cooldown,
        syncCooldown,
        getVisible,
        addOrUpdate,
        evictOutside
    }
}
