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
    padding: 5px 8px;
    border-radius: 4px;
    font-size: 16px;
    color: white;
    background-color: #00000080;
    width: 180px;
    text-align: center;
}

@media (max-width: 768px) {
    #pixel-info {
        bottom: 200px;
    }
}
</style>
