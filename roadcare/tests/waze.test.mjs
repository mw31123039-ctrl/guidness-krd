import { test } from 'node:test';
import assert from 'node:assert/strict';
import { coordinates, parseWazeLocation, wazeEmbedUrl, wazeLocationUrl } from '../resources/js/waze.js';

test('imports exact latitude and longitude from Waze location links', () => {
    for (const host of ['www.waze.com', 'waze.com', 'ul.waze.com']) {
        const point = parseWazeLocation(`https://${host}/ul?ll=36.1911234%2C44.0092345&navigate=yes`);

        assert.deepEqual(point, { lat: 36.1911234, lng: 44.0092345 });
    }
});

test('imports the directions destination instead of the origin or map center', () => {
    const link = 'https://www.waze.com/live-map/directions?from=ll.35.557,45.435&ll=35.557,45.435&to=ll.36.1911%2C44.0092';

    const point = parseWazeLocation(link);

    assert.deepEqual(point, { lat: 36.1911, lng: 44.0092 });
});

test('imports a Waze embed location using its latitude and longitude', () => {
    const point = parseWazeLocation('https://embed.waze.com/iframe?lat=35.177&lon=45.986&zoom=16&pin=1');

    assert.deepEqual(point, { lat: 35.177, lng: 45.986 });
});

test('rejects links without an exact destination instead of guessing a location', () => {
    for (const link of [
        'https://www.waze.com/ul?q=Erbil',
        'https://www.waze.com/ul/h123456789',
        'https://www.waze.com/live-map/directions?to=place.123&ll=36.1911,44.0092',
    ]) {
        assert.throws(() => parseWazeLocation(link), { message: 'wazeCoordinatesMissing' });
    }
});

test('rejects other websites, unsafe schemes, credentials, ports, and ambiguous parameters', () => {
    for (const link of [
        '', 'not a URL', 'https://example.com/?ll=36,44', 'https://waze.com.example.com/ul?ll=36,44',
        'http://waze.com/ul?ll=36,44', 'javascript:alert(1)', 'https://waze.com@evil.example/ul?ll=36,44',
        'https://user:password@www.waze.com/ul?ll=36,44', 'https://www.waze.com:1234/ul?ll=36,44',
        'https://www.waze.com/ul?ll=36,44&ll=35,45', 'https://waze.com/?ll=36,44&to=ll.35,45&to=ll.37,43',
    ]) {
        assert.throws(() => parseWazeLocation(link), { message: 'wazeInvalidLink' });
    }
});

test('rejects missing, malformed, and out-of-range coordinates', () => {
    for (const pair of ['36,', ',44', '36,44,45', 'NaN,44', '91,44', '-91,44', '36,181', '36,-181', 'Infinity,44', '0x24,44', '1e1,44']) {
        assert.throws(() => parseWazeLocation(`https://www.waze.com/ul?ll=${encodeURIComponent(pair)}`), { message: 'wazeInvalidCoordinates' });
    }
});

test('keeps negative and zero coordinates valid without treating blank inputs as zero', () => {
    assert.deepEqual(coordinates('-12.12345678', '0'), { lat: -12.1234568, lng: 0 });
    assert.throws(() => coordinates('', 44), { message: 'wazeInvalidCoordinates' });
    assert.throws(() => coordinates(null, 44), { message: 'wazeInvalidCoordinates' });
});

test('starts a regional map without silently creating a report pin', () => {
    const url = new URL(wazeEmbedUrl());

    assert.equal(url.origin, 'https://embed.waze.com');
    assert.equal(url.searchParams.get('pin'), null);
    assert.equal(url.searchParams.get('zoom'), '8');
});

test('centers the Waze iframe on the selected exact location and shows its pin', () => {
    const url = new URL(wazeEmbedUrl({ lat: 36.1911, lng: 44.0092 }));

    assert.equal(url.searchParams.get('lat'), '36.1911');
    assert.equal(url.searchParams.get('lon'), '44.0092');
    assert.equal(url.searchParams.get('pin'), '1');
    assert.equal(url.searchParams.get('zoom'), '16');
});

test('opens Waze navigation at the saved coordinates only when navigation is requested', () => {
    const point = { lat: 35.177, lng: 45.986 };

    const view = new URL(wazeLocationUrl(point));
    const navigation = new URL(wazeLocationUrl(point, true));

    assert.equal(view.origin, 'https://www.waze.com');
    assert.equal(view.searchParams.get('ll'), '35.177,45.986');
    assert.equal(view.searchParams.get('navigate'), null);
    assert.equal(navigation.searchParams.get('navigate'), 'yes');
    assert.equal(navigation.searchParams.get('ll'), '35.177,45.986');
});
