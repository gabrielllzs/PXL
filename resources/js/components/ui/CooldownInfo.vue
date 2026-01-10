<template>
    <div id="cooldown" v-if="seconds > 0">
        {{ formatted }}
    </div>

</template>

<script setup>
import { computed } from 'vue'

const props =  defineProps({
    seconds: {
        type: Number,
        required: true
    }
})


const formatted = computed(() => {
    const minutes = Math.floor(props.seconds / 60)
    const secs = props.seconds % 60
    const mm = String(minutes).padStart(2,'0')
    const ss = String(secs).padStart(2,'0')
    return `${mm}:${ss}`
})
</script>

<style scoped>

#cooldown {
    position: absolute;
    top: 30px;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align:center;
    background: rgba(255,255,255,0.92);
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 16px;
    color: #333;
    z-index: 5;
}
</style>
