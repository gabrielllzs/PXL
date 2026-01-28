export function lngLatToWorldPx(lngLat, zoom) {
    const scale = 256 * Math.pow(2, zoom)
    const x = (lngLat.lng + 180) / 360 * scale
    const latRad = lngLat.lat * Math.PI / 180
    const mercN = Math.log(Math.tan(Math.PI / 4 + latRad / 2))
    const y = scale / 2 - mercN * scale / (2 * Math.PI)
    return { x, y }
}

export function worldPxToLngLat(worldPx, zoom) {
    const scale = 256 * Math.pow(2, zoom)
    const lng = worldPx.x / scale * 360 - 180
    const mercN = (scale / 2 - worldPx.y) * 2 * Math.PI / scale
    const latRad = 2 * Math.atan(Math.exp(mercN)) - Math.PI / 2
    return { lng, lat: latRad * 180 / Math.PI }
}
export function pixelsToGeoJSON(pixels, zoom) {
    const features = []
    for (const p of pixels) {
        const nw = worldPxToLngLat({ x: p.x, y: p.y }, zoom)
        const ne = worldPxToLngLat({ x: p.x + 1, y: p.y }, zoom)
        const se = worldPxToLngLat({ x: p.x + 1, y: p.y + 1 }, zoom)
        const sw = worldPxToLngLat({ x: p.x, y: p.y + 1 }, zoom)
        const coords = [
            [nw.lng, nw.lat],
            [ne.lng, ne.lat],
            [se.lng, se.lat],
            [sw.lng, sw.lat],
            [nw.lng, nw.lat]
        ]
        features.push({
            type: 'Feature',
            geometry: { type: 'Polygon', coordinates: [coords] },
            properties: { color: p.color || '#000000' }
        })
    }
    return { type: 'FeatureCollection', features }
}
