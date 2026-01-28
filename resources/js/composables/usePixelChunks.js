const CHUNK_SIZE = 128

const chunks = new Map()

function chunkKey(x, y) {
    const cx = Math.floor(x / CHUNK_SIZE)
    const cy = Math.floor(y / CHUNK_SIZE)
    return `${cx},${cy}`
}

function getChunkOrCreate(key) {
    let arr = chunks.get(key)
    if (!arr) {
        arr = []
        chunks.set(key, arr)
    }
    return arr
}

export function loadAll(pixels) {
    chunks.clear()
    if (!Array.isArray(pixels)) return
    for (const p of pixels) {
        addOrUpdate(p.x, p.y, p.color ?? '#000000')
    }
}

export function addOrUpdate(x, y, color) {
    const key = chunkKey(x, y)
    const arr = getChunkOrCreate(key)
    const i = arr.findIndex((q) => q.x === x && q.y === y)
    if (i >= 0) {
        arr[i].color = color
    } else {
        arr.push({ x, y, color })
    }
}

/**
 * Remove chunks that do not intersect the given world-pixel bounds.
 * Call before fetching new viewport to keep memory bounded.
 */
export function evictOutside(minX, maxX, minY, maxY) {
    const cMinX = Math.floor(minX / CHUNK_SIZE)
    const cMaxX = Math.floor(maxX / CHUNK_SIZE)
    const cMinY = Math.floor(minY / CHUNK_SIZE)
    const cMaxY = Math.floor(maxY / CHUNK_SIZE)
    for (const key of chunks.keys()) {
        const [cx, cy] = key.split(',').map(Number)
        if (cx < cMinX || cx > cMaxX || cy < cMinY || cy > cMaxY) {
            chunks.delete(key)
        }
    }
}

/**
 * Return pixels that intersect the given world-pixel bounds.
 * Only iterates chunks that overlap the bounds.
 */
export function getVisible(minX, maxX, minY, maxY) {
    const out = []
    const cMinX = Math.floor(minX / CHUNK_SIZE)
    const cMaxX = Math.floor(maxX / CHUNK_SIZE)
    const cMinY = Math.floor(minY / CHUNK_SIZE)
    const cMaxY = Math.floor(maxY / CHUNK_SIZE)

    for (let cy = cMinY; cy <= cMaxY; cy++) {
        for (let cx = cMinX; cx <= cMaxX; cx++) {
            const key = `${cx},${cy}`
            const arr = chunks.get(key)
            if (!arr) continue
            for (const p of arr) {
                if (p.x >= minX && p.x <= maxX && p.y >= minY && p.y <= maxY) {
                    out.push(p)
                }
            }
        }
    }
    return out
}

export function usePixelChunks() {
    return { loadAll, addOrUpdate, getVisible, evictOutside }
}
