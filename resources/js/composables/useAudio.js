import pixelPlaceSound from '../../audio/pixel-place.wav'
import {ref} from "vue";

let audioElement = null
export const audioEnabled = ref(true)

// Throttle audio to prevent glitches when placing fast
const MIN_AUDIO_INTERVAL = 80 // ms
let lastPlayTime = 0

function getAudioElement() {
    if (!audioElement) {
        audioElement = new Audio(pixelPlaceSound)
        audioElement.volume = 1.0
        audioElement.preload = 'auto'
    }
    return audioElement
}

export function playPixelPlaceSound() {
    if (!audioEnabled.value) return
    
    const now = Date.now()
    if (now - lastPlayTime < MIN_AUDIO_INTERVAL) return
    lastPlayTime = now
    
    try {
        const audio = getAudioElement()
        audio.currentTime = 0
        audio.play().catch(() => {})
    } catch {}
}
