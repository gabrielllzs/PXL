import { ref, onUnmounted } from 'vue'

let maplibreCssLoaded = false

function loadMapLibreCSS() {
    if (maplibreCssLoaded) return Promise.resolve()
    
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (document.querySelector('link[href*="maplibre-gl.css"]')) {
            maplibreCssLoaded = true
            resolve()
            return
        }
        
        const link = document.createElement('link')
        link.rel = 'stylesheet'
        link.href = 'https://unpkg.com/maplibre-gl/dist/maplibre-gl.css'
        link.crossOrigin = 'anonymous'
        link.onload = () => {
            maplibreCssLoaded = true
            resolve()
        }
        link.onerror = reject
        document.head.appendChild(link)
    })
}

export function useMap(containerId = 'map') {
    const map = ref(null)

    async function init(options = {}) {
        // Load CSS first, then JS
        await loadMapLibreCSS()
        const maplibregl = (await import('maplibre-gl')).default;
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
            zoom: 11,
            dragRotate: false,
            attributionControl: false,
            touchPitch: false,
            touchRotate: false,
            bearing: 0,
            pitch: 0,
            maxPitch: 0,
        })
        map.value.touchZoomRotate.disableRotation()
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

    function getBounds() {
        return map.value?.getBounds()
    }

    function getZoom(){

       return map.value?.getZoom()
    }

    onUnmounted(() => {
        map.value?.remove()
        map.value = null
    })

    const zoomIn = () => map.value?.zoomIn();
    const zoomOut = () => map.value?.zoomOut();
    const centerMap = () => map.value?.flyTo({ center: [0, 0], zoom: 10 });


    return { map, init, on, off, project, unproject,  zoomIn, zoomOut, centerMap, getBounds, getZoom }
}
