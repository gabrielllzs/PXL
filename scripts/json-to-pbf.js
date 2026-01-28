#!/usr/bin/env node

import { readFileSync } from 'fs';
import geojsonvt from 'geojson-vt';
import { fromGeojsonVt } from '@maplibre/vt-pbf';

const input = JSON.parse(readFileSync(0, 'utf-8'));
const { pixels, z, x, y, zoom } = input;

// Convert world pixel coordinates to lat/lng (same as pixelsToGeoJSON)
function worldPxToLngLat(worldPx, zoom) {
    const scale = 256 * Math.pow(2, zoom);
    const lng = worldPx.x / scale * 360 - 180;
    const mercN = (scale / 2 - worldPx.y) * 2 * Math.PI / scale;
    const latRad = 2 * Math.atan(Math.exp(mercN)) - Math.PI / 2;
    return { lng, lat: latRad * 180 / Math.PI };
}

// Convert pixels to GeoJSON
const features = [];
for (const p of pixels) {
    const nw = worldPxToLngLat({ x: p.x, y: p.y }, zoom);
    const ne = worldPxToLngLat({ x: p.x + 1, y: p.y }, zoom);
    const se = worldPxToLngLat({ x: p.x + 1, y: p.y + 1 }, zoom);
    const sw = worldPxToLngLat({ x: p.x, y: p.y + 1 }, zoom);
    
    const coords = [
        [nw.lng, nw.lat],
        [ne.lng, ne.lat],
        [se.lng, se.lat],
        [sw.lng, sw.lat],
        [nw.lng, nw.lat]
    ];
    features.push({
        type: 'Feature',
        geometry: { type: 'Polygon', coordinates: [coords] },
        properties: { color: p.color || '#000000' }
    });
}

const geojson = { type: 'FeatureCollection', features };
const index = geojsonvt(geojson, { extent: 4096, maxZoom: 24 });
const tile = index.getTile(z, x, y);

try {
    // Use vector tile spec v2 to avoid warnings in MapLibre GL JS
    const options = { version: 2, extent: 4096 };
    if (!tile || !tile.features?.length) {
        const empty = fromGeojsonVt({ pixels: { features: [] } }, options);
        process.stdout.write(empty);
    } else {
        const pbf = fromGeojsonVt({ pixels: tile }, options);
        process.stdout.write(pbf);
    }
} catch (error) {
    console.error('PBF generation error:', error);
    process.exit(1);
}
