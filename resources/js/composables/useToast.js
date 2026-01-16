import { reactive } from 'vue'

const toastState = reactive({
    list: []
})

let toastCounter = 0

export function useToast() {

    function addToast(message, type = 'info', duration = 3000) {
        const toastId = ++toastCounter
        toastState.list.push({ id: toastId, message, type })

        if (duration > 0) {
            setTimeout(() => removeToast(toastId), duration)
        }
    }

    function removeToast(id) {
        const index = toastState.list.findIndex(toast => toast.id === id)
        if (index !== -1) toastState.list.splice(index, 1)
    }

    return {
        toasts: toastState.list,
        showToast: addToast,
        removeToast
    }
}
