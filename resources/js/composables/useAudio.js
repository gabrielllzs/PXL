import pixelPlaceSound from '../../audio/pixel-place.wav'
import {ref} from "vue";

let audioElement = null
export const audioEnabled = ref(true)

function getAudioElement() {
    if (!audioElement) {
        audioElement = new Audio(pixelPlaceSound)
        audioElement.volume = 1.0
        audioElement.preload = 'auto'
    }
    return audioElement
}

export function playPixelPlaceSound() {
    if (audioEnabled.value) {
        try {
            const audio = getAudioElement()

            audio.currentTime = 0
            audio.play().catch(err => {
                console.debug('Audio playback failed:', err)
            })
        } catch (err) {
            console.debug('Audio playback failed:', err)
        }
    }
}
