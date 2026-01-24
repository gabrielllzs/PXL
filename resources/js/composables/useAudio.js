import pixelPlaceSound from '../../audio/pixel-place.wav'
import {ref} from "vue";

export const audioEnabled = ref(true)

const POOL_SIZE = 4
const audioPool = []
let poolIndex = 0
let lastPlayTime = 0
const MIN_AUDIO_INTERVAL = 50

function initAudioPool() {
    if (audioPool.length > 0) return

    for (let i = 0; i < POOL_SIZE; i++) {
        const audio = new Audio(pixelPlaceSound)
        audio.volume = 0.8
        audio.preload = 'auto'
        audioPool.push(audio)
    }
}

export function playPixelPlaceSound() {
    if (!audioEnabled.value) return

    const now = Date.now()
    if (now - lastPlayTime < MIN_AUDIO_INTERVAL) return
    lastPlayTime = now

    try {
        initAudioPool()

        // Use next audio element in pool
        const audio = audioPool[poolIndex]
        poolIndex = (poolIndex + 1) % POOL_SIZE

        // Only play if not already playing, otherwise skip
        if (audio.paused || audio.ended) {
            audio.currentTime = 0
            audio.play().catch(() => {})
        }
    } catch {}
}
