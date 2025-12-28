<script setup>
import { ref } from 'vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    connect: {
        type: Function,
        required: true
    }
})

const buyer = ref(null)
const connecting = ref(false)
const { showToast } = useToast()

async function handleConnect() {
    await props.connect({
        showToast,
        setConnecting: v => (connecting.value = v),
        setBuyer: v => (buyer.value = v)
    })
}
</script>

<template>
    <button @click="handleConnect" class="wallet-btn">
        <img src="../../images/phantom.png" alt="phantom logo in pixel art" />
        <span v-if="connecting">Connecting...</span>
        <span v-else-if="buyer">Connected</span>
    </button>
</template>


<style scoped>
.wallet-btn {
    position: fixed;
    top: 10px;
    right: 10px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    pointer-events: auto;
    cursor: pointer;
}

.wallet-btn img
{
    width: 26px;
    display: block;
}

</style>
