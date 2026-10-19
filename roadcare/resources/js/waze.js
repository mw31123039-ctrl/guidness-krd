const wazeHosts = new Set(['waze.com', 'www.waze.com', 'ul.waze.com', 'embed.waze.com']);
const regionalCenter = { lat: 36.1911, lng: 44.0092 };

export function coordinates(latitude, longitude) {
    const decimal = /^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/;
    const values = [latitude, longitude].map(value => {
        if (typeof value !== 'number' && (typeof value !== 'string' || !decimal.test(value.trim()))) {
            throw new Error('wazeInvalidCoordinates');
        }
        return Number(value);
    });
    const [lat, lng] = values;
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
        throw new Error('wazeInvalidCoordinates');
    }
    return { lat: Number(lat.toFixed(7)), lng: Number(lng.toFixed(7)) };
}

export function parseWazeLocation(value) {
    let url;
    try {
        if (typeof value !== 'string' || value.length > 4096) throw new Error();
        url = new URL(value.trim());
    } catch {
        throw new Error('wazeInvalidLink');
    }
    if (url.protocol !== 'https:' || !wazeHosts.has(url.hostname) || url.username || url.password || url.port) {
        throw new Error('wazeInvalidLink');
    }
    for (const parameter of ['to', 'll', 'lat', 'lon']) {
        if (url.searchParams.getAll(parameter).length > 1) throw new Error('wazeInvalidLink');
    }

    const destination = url.searchParams.get('to');
    let pair;
    if (destination !== null) {
        // A directions link must use its destination, never the map center or origin.
        if (!destination.startsWith('ll.')) throw new Error('wazeCoordinatesMissing');
        pair = destination.slice(3).split(',');
    } else if (url.searchParams.has('ll')) {
        pair = url.searchParams.get('ll').split(',');
    } else if (url.searchParams.has('lat') && url.searchParams.has('lon')) {
        pair = [url.searchParams.get('lat'), url.searchParams.get('lon')];
    } else {
        throw new Error('wazeCoordinatesMissing');
    }
    if (pair.length !== 2) throw new Error('wazeInvalidCoordinates');
    return coordinates(...pair);
}

export function wazeEmbedUrl(position = null) {
    const point = position ? coordinates(position.lat, position.lng) : regionalCenter;
    const query = new URLSearchParams({ zoom: position ? '16' : '8', lat: String(point.lat), lon: String(point.lng) });
    if (position) query.set('pin', '1');
    return `https://embed.waze.com/iframe?${query}`;
}

export function wazeLocationUrl(position = null, navigate = false) {
    const point = position ? coordinates(position.lat, position.lng) : regionalCenter;
    const query = new URLSearchParams({ ll: `${point.lat},${point.lng}`, z: '16', utm_source: 'roadcare' });
    if (navigate) query.set('navigate', 'yes');
    return `https://www.waze.com/ul?${query}`;
}
