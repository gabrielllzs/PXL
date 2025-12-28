import { reactive } from 'vue'

const state = reactive({
    toasts: []
})
let nextId = 0

export function useToast() {
    function showToast(message, type = 'info', timeout = 3000) {
        const id = ++nextId
        state.toasts.push({ id, message, type })
        if (timeout > 0) {
            setTimeout(() => removeToast(id), timeout)
        }
    }

    function removeToast(id) {
        const idx = state.toasts.findIndex(t => t.id === id)
        if (idx !== -1) state.toasts.splice(idx, 1)
    }

    return {
        toasts: state.toasts,
        showToast,
        removeToast
    }
}
