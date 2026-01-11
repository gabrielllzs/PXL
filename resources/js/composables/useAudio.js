import pixelPlaceSound from '../../audio/pixel-place.wav'

let audioElement = null

function getAudioElement() {
    if (!audioElement) {
        audioElement = new Audio(pixelPlaceSound)
        audioElement.volume = 1.0
        audioElement.preload = 'auto'
    }
    return audioElement
}

export function playPixelPlaceSound() {
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
