import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useNpcMovement(speed = 0.1, startOffset = 100) {
    const x = ref(-startOffset)
    const y = ref(-startOffset)


    const targetX = ref(window.innerWidth / 2)
    const targetY = ref(window.innerHeight / 2)

    let animationFrameId = null

    const updateTarget = (e) => {
        targetX.value = e.clientX
        targetY.value = e.clientY
    }

    const animate = () => {
        const dx = targetX.value - x.value
        const dy = targetY.value - y.value

        x.value += dx * speed
        y.value += dy * speed

        animationFrameId = requestAnimationFrame(animate)
    }

    onMounted(() => {
        window.addEventListener('mousemove', updateTarget)
        animate()
    })

    onBeforeUnmount(() => {
        window.removeEventListener('mousemove', updateTarget)
        cancelAnimationFrame(animationFrameId)
    })

    return { x, y, targetX, targetY }
}
