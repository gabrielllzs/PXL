import { reactive } from 'vue'
import axios from 'axios'
import FingerprintJS from '@fingerprintjs/fingerprintjs'
import { useWallet } from './connectWallet.js'

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
        const { buyer, walletSignature, hasReduction } = useWallet()


        if (cooldown.active) return false

        try {

            const data = {
                x,
                y,
                color,
                visitorId,
                captchaToken,
                wallet: buyer.value && walletSignature.value ? {
                    publicKey: buyer.value,
                    signature: walletSignature.value.signature,
                    message: walletSignature.value.message,
                } : null,
                components: fpComponents,
            }

            const response = await axios.post('/api/pixel', data)

            const existing = stored.find(p => p.x === x && p.y === y)
            if (existing) {
                existing.color = color
                if (response.data?.id) existing.id = response.data.id
            } else {
                stored.push({ x, y, color, id: response.data?.id })
            }

            const cooldownDuration = hasReduction.value ? 5 : 10

            startCooldown(cooldownDuration)
            return true
        } catch (err) {
            if (err.response?.status === 412) {
                const remaining = err.response.data?.remaining ?? 1
                startCooldown(remaining)
                return false
            }else if (err.response?.status === 403) {
                console.error('Security Blocked');
                return false;
            }else {
                console.error('Unexpected error:', err)
                return false
            }
        }
    }

    async function syncCooldown() {
        await initFingerprint()
        try {
            const res = await axios.get('/api/cooldown', { params: { visitorId } })
            const remaining = res.data.remaining || 0
            if (remaining > 0) startCooldown(remaining)
        } catch (err) {
            console.error('Failed to sync cooldown (likely server or network issue):', err);
        }
    }

    return { stored, load, save, cooldown, syncCooldown }
}
