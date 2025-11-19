<template>
    <div id="pixel-info" v-if="info">
        {{ info }}
    </div>
</template>

<script setup>
defineProps({
    info: {
        type: String,
        default: ''
    }
})
</script>

<style scoped>
#pixel-info {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: white;
    padding: 5px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 12px;
}
</style>
