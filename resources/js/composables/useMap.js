import { ref, onUnmounted } from 'vue'
import maplibregl from 'maplibre-gl'

export function useMap(containerId = 'map') {
    const map = ref(null)

    function init(options = {}) {
        map.value = new maplibregl.Map({
            container: containerId,
            style: {
                version: 8,
                name: 'blank',
                sources: {},
                layers: [
                    { id: 'background', type: 'background', paint: { 'background-color': 'rgba(132,176,245)' } }
                ]
            },
            center: [0, 0],
            zoom: 10,
            dragRotate: false,
            touchZoomRotate: false,
            pitchWithRotate: false,
        })
        return map.value
    }

    function on(event, callback) {
        map.value?.on(event, callback)
    }

    function off(event, callback) {
        map.value?.off(event, callback)
    }

    function project(lngLat) {
        return map.value?.project(lngLat)
    }

    function unproject(point) {
        return map.value?.unproject(point)
    }

    onUnmounted(() => {
        map.value?.remove()
        map.value = null
    })

    return { map, init, on, off, project, unproject }
}
