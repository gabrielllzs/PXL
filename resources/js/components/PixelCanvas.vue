<template>
    <div id="map" style="height: 100vh; width: 100vw;"></div>
</template>

<script setup>
import { onMounted } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

onMounted(() => {
    const map = new maplibregl.Map({
        container: 'map', // container id
        style: 'https://tiles.openfreemap.org/styles/liberty',
        center: [0, 0], // starting position [lng, lat]
        zoom: 1 // starting zoom
    });
});
</script>

<style scoped>

</style>
