<template>
    <div id="cooldown" v-if="seconds > 0">
        Cooldown active. Please wait {{ seconds }}s.
    </div>
</template>

<script setup>
defineProps({
    seconds: {
        type: Number,
        required: true
    }
})
</script>

<style scoped>
#cooldown {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(255, 255, 255, 0.95);
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 12px;
    color: #333;
}
</style>
