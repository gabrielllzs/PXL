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
