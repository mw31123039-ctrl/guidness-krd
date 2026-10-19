<script setup>
import boundaryText from '../../geo/kurdistan.geojson?raw';
import { useI18n } from '../i18n';

const { t, locale } = useI18n();
const boundary = JSON.parse(boundaryText);
const regions = boundary.features.map(feature => ({
    name: feature.properties.shapeISO,
    polygons: feature.geometry.type === 'Polygon' ? [feature.geometry.coordinates] : feature.geometry.coordinates,
}));
const vertices = regions.flatMap(region => region.polygons.flatMap(polygon => polygon.flat()));
const longitudes = vertices.map(point => point[0]);
const latitudes = vertices.map(point => point[1]);
const west = Math.min(...longitudes), east = Math.max(...longitudes);
const south = Math.min(...latitudes), north = Math.max(...latitudes);
const longitudeScale = Math.cos((north + south) / 2 * Math.PI / 180);
const scale = Math.min(452 / ((east - west) * longitudeScale), 264 / (north - south));
const offsetX = (500 - (east - west) * longitudeScale * scale) / 2;
const offsetY = (300 - (north - south) * scale) / 2;

function project(longitude, latitude) {
    return [offsetX + (longitude - west) * longitudeScale * scale, offsetY + (north - latitude) * scale];
}
function outline(polygons) {
    return polygons.map(polygon => polygon.map(ring => ring.map(([longitude, latitude], index) => {
        const [x, y] = project(longitude, latitude);
        return `${index ? 'L' : 'M'}${x.toFixed(2)},${y.toFixed(2)}`;
    }).join(' ') + ' Z').join(' ')).join(' ');
}
const cities = [
    { name: 'Duhok', longitude: 42.95, latitude: 36.867 },
    { name: 'Erbil', longitude: 44.0092, latitude: 36.1911 },
    { name: 'Sulaymaniyah', longitude: 45.435, latitude: 35.557 },
    { name: 'Halabja', longitude: 45.986, latitude: 35.177 },
].map(city => ({ ...city, point: project(city.longitude, city.latitude) }));
</script>
<template>
    <svg class="auth-region-map" viewBox="0 0 500 300" role="img" :aria-label="`${t('region')}: ${t('coverage')}`">
        <defs>
            <linearGradient id="auth-region-fill" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#1b5a6b" stop-opacity=".8" /><stop offset="1" stop-color="#143849" stop-opacity=".55" /></linearGradient>
            <radialGradient id="auth-region-glow"><stop stop-color="#19c6df" stop-opacity=".12" /><stop offset="1" stop-color="#19c6df" stop-opacity="0" /></radialGradient>
        </defs>
        <ellipse cx="250" cy="150" rx="235" ry="140" fill="url(#auth-region-glow)" />
        <g class="auth-region-boundaries">
            <path v-for="region in regions" :key="region.name" :d="outline(region.polygons)" fill="url(#auth-region-fill)" fill-rule="evenodd" stroke="#3297aa" stroke-width="1.2" stroke-linejoin="round" />
        </g>
        <g v-for="city in cities" :key="city.name" :transform="`translate(${city.point.join(',')})`" class="auth-region-city">
            <circle r="10" fill="#19c6df" fill-opacity=".08" stroke="#27aec3" stroke-opacity=".35" stroke-width=".7" />
            <circle r="3.2" fill="#68e2ed" stroke="#123343" stroke-width="1.3" />
            <text x="14" y="4" :direction="locale === 'ckb' ? 'rtl' : 'ltr'" :text-anchor="locale === 'ckb' ? 'end' : 'start'">{{ t(city.name) }}</text>
        </g>
    </svg>
</template>
