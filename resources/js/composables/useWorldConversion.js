export function lngLatToWorldPx(lngLat, zoom) {
    const scale = Math.pow(2, zoom)
    const worldSize = 256 * scale
    const x = (lngLat.lng + 180) / 360 * worldSize
    const latRad = lngLat.lat * Math.PI / 180
    const mercN = Math.log(Math.tan(Math.PI / 4 + latRad / 2))
    const y = worldSize / 2 - (mercN * worldSize / (2 * Math.PI))
    return { x, y }
}

export function worldPxToLngLat(worldPx, zoom) {
    const scale = Math.pow(2, zoom)
    const worldSize = 256 * scale
    const lng = (worldPx.x / worldSize) * 360 - 180
    const mercN = (worldSize / 2 - worldPx.y) * (2 * Math.PI) / worldSize
    const latRad = 2 * Math.atan(Math.exp(mercN)) - Math.PI / 2
    return { lng, lat: latRad * 180 / Math.PI }
}
