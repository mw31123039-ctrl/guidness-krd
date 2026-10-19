import { chromium, expect } from '@playwright/test';
import { existsSync, mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn, spawnSync } from 'node:child_process';

// A separate temporary database and photo directory keep this test away from user data.
const root = fileURLToPath(new URL('../', import.meta.url));
const temp = mkdtempSync(join(tmpdir(), 'roadcare-browser-'));
const database = join(temp, 'browser.sqlite');
writeFileSync(database, '');
const port = 8011;
const base = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'true', APP_URL: base, DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', CACHE_STORE: 'array', SESSION_DRIVER: 'database', BCRYPT_ROUNDS: '4', ROADCARE_PRIVATE_STORAGE: join(temp, 'photos') };
const migrate = spawnSync('php', ['artisan', 'migrate', '--seed', '--force', '--no-interaction'], { cwd: root, env, encoding: 'utf8', windowsHide: true });
if (migrate.status !== 0) throw new Error(migrate.stdout + migrate.stderr);
if (!existsSync(join(root, 'public/build/manifest.json'))) throw new Error('Run npm run build before browser tests.');
const server = spawn('php', ['-d', 'upload_max_filesize=10M', '-d', 'post_max_size=12M', '-S', `127.0.0.1:${port}`, join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: join(root, 'public'), env, stdio: 'pipe', windowsHide: true });
let serverOutput = '';
server.stdout.on('data', data => serverOutput += data);
server.stderr.on('data', data => serverOutput += data);
server.on('error', error => console.error(error));
const candidates = [process.env.ROADCARE_BROWSER_PATH, 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', 'C:/Program Files/Google/Chrome/Application/chrome.exe'];
const executablePath = candidates.find(path => path && existsSync(path));
let browser;
const artifacts = join(root, 'artifacts'); mkdirSync(artifacts, { recursive: true });
try {
    for (let attempt = 0; attempt < 40; attempt++) {
        try { if ((await fetch(`${base}/login`)).ok) break; } catch {}
        if (attempt === 39) throw new Error(`Browser test server failed: ${serverOutput.slice(-3000)}`);
        await new Promise(resolve => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true, ...(executablePath ? { executablePath } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
    // Test RoadCare's import and saved pin behavior independently of Waze's network availability.
    await context.route('https://embed.waze.com/**', route => route.fulfill({ contentType: 'text/html', body: '<!doctype html><html><body style="font:14px sans-serif;padding:30px;color:#526476">Waze iframe isolated for automated testing.</body></html>' }));
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'warning' && message.text().includes('[Vue warn]')) errors.push(message.text()); });
    async function shot(name) { await page.screenshot({ path: join(artifacts, `${name}.png`), fullPage: true }); }
    async function login(email) {
        await page.goto(`${base}/login`);
        await page.locator('input[type=email]').fill(email);
        await page.locator('input[type=password]').fill('RoadCare123!');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await expect(page).toHaveURL(email.startsWith('government') ? `${base}/government` : `${base}/dashboard`);
        await expect(page.locator('h1')).toBeVisible();
    }
    await page.goto(`${base}/login`);
    await expect(page.getByRole('heading', { name: 'Welcome to RoadCare' })).toBeVisible();
    await shot('login-desktop');
    await login('citizen@demo.com');
    await shot('citizen-dashboard');
    await page.getByRole('button', { name: 'Switch to Kurdish Sorani' }).click();
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('h1')).toContainText('بەخێربێیتەوە');
    await shot('dashboard-sorani');
    await page.getByRole('button', { name: 'Switch to English' }).click();
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
    await page.getByRole('link', { name: 'Report road damage', exact: true }).first().click();
    await expect(page).toHaveURL(`${base}/reports/create`);
    await expect(page.getByLabel('Latitude')).toHaveValue('');
    await expect(page.getByLabel('Longitude')).toHaveValue('');
    await page.getByLabel('Waze location share link').fill('https://example.com/ul?ll=36,44');
    await page.getByRole('button', { name: 'Use location', exact: true }).click();
    await expect(page.getByText('Paste a full HTTPS location link from Waze.')).toBeVisible();
    await page.getByLabel('Report title').fill('Browser check: pothole near the university');
    await page.getByLabel('What did you notice?').fill('A deep pothole is visible beside the university entrance and needs a repair.');
    await page.getByLabel('Street or nearby landmark').fill('University road, main entrance');
    const fixturePhoto = await page.locator('.upload-camera').screenshot();
    // Exercise an actual multipart upload beyond PHP's default 2 MB limit.
    await page.locator('#report-photo').setInputFiles({ name: 'road.png', mimeType: 'image/png', buffer: Buffer.concat([fixturePhoto, Buffer.alloc(3 * 1024 * 1024)]) });
    await page.getByLabel('Waze location share link').fill('https://www.waze.com/ul?ll=33.3152%2C44.3661');
    await page.getByRole('button', { name: 'Use location', exact: true }).click();
    await page.getByRole('button', { name: 'Send report', exact: true }).click();
    await expect(page.getByText('Choose a location within the Kurdistan Region service area.')).toBeVisible();
    await page.getByLabel('Waze location share link').fill('https://www.waze.com/live-map/directions?from=ll.35.557,45.435&ll=35.557,45.435&to=ll.36.1911%2C44.0092');
    await page.getByRole('button', { name: 'Use location', exact: true }).click();
    await expect(page.getByLabel('Latitude')).toHaveValue('36.1911');
    await expect(page.getByLabel('Longitude')).toHaveValue('44.0092');
    await expect(page.locator('.waze-frame')).toHaveAttribute('src', /lat=36\.1911&lon=44\.0092&pin=1/);
    await page.getByRole('button', { name: 'Send report', exact: true }).click();
    await expect(page).toHaveURL(/\/reports\/\d+$/);
    const reportUrl = page.url();
    await expect(page.locator('.report-photo')).toBeVisible();
    await expect(page.locator('.detail-heading .badge')).toHaveText('Submitted');
    await expect(page.locator('.coordinates')).toHaveText('36.191100, 44.009200');
    await expect(page.getByRole('link', { name: 'Navigate with Waze' })).toHaveAttribute('href', /ll=36\.1911%2C44\.0092.*navigate=yes/);
    await shot('citizen-report');
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await login('government@roadcare.test');
    await page.goto(`${base}/government/reports`);
    await page.getByRole('textbox', { name: 'Search', exact: true }).fill('Browser check:');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.locator('.report-table tbody tr')).toHaveCount(1);
    await page.getByRole('link', { name: 'Browser check: pothole near the university' }).click();
    for (const [status, label] of [['reviewing', 'Under review'], ['scheduled', 'Repair scheduled'], ['in_progress', 'Repair in progress'], ['resolved', 'Resolved']]) {
        await page.getByLabel('Update status').selectOption(status);
        await page.getByLabel('Responsible department').fill('Erbil Road Maintenance');
        await page.getByLabel('Update for the citizen').fill(`Browser verification: ${label}. The maintenance team has updated your report.`);
        await page.getByRole('button', { name: 'Save update', exact: true }).click();
        await expect(page.locator('.detail-heading .badge')).toHaveText(label);
    }
    await expect(page.locator('.timeline li')).toHaveCount(5);
    await shot('government-review');
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await login('citizen@demo.com');
    await page.goto(reportUrl);
    await expect(page.locator('.detail-heading .badge')).toHaveText('Resolved');
    await expect(page.locator('.government-action')).toHaveCount(0);
    const guest = await browser.newContext();
    const guestPage = await guest.newPage();
    await guestPage.goto(`${reportUrl}/photo`);
    await expect(guestPage).toHaveURL(`${base}/login`);
    await guest.close();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${base}/dashboard`);
    await expect(page.locator('h1')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await shot('dashboard-mobile');
    await page.getByRole('button', { name: 'Toggle navigation' }).click();
    await expect(page.locator('.sidebar')).toHaveClass(/is-open/);
    await page.locator('.sidebar').getByRole('link', { name: 'Regional map' }).click();
    await expect(page).toHaveURL(`${base}/map`);
    await expect(page.locator('.waze-frame')).toHaveAttribute('src', /^https:\/\/embed\.waze\.com\/iframe\?/);
    await page.getByRole('button', { name: 'Show on map', exact: true }).first().click();
    await expect(page.locator('.waze-frame')).toHaveAttribute('src', /pin=1/);
    await page.getByRole('button', { name: 'Switch to Kurdish Sorani' }).click();
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await expect(page.locator('.sidebar')).toBeHidden();
    await shot('map-mobile-sorani');
    await page.getByRole('button', { name: 'Toggle navigation' }).click();
    await page.locator('.sidebar a[href="/reports/create"]').click();
    await expect(page).toHaveURL(`${base}/reports/create`);
    await expect(page.locator('.sidebar')).toBeHidden();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await shot('report-mobile-sorani');
    expect(errors).toEqual([]);
    console.log('Browser checks passed: Waze share-link import, exact saved coordinates, iframe pin, report selection, navigation links, citizen photo submission, regional validation, government review-to-resolution, privacy, English/Sorani and mobile layout. Waze network isolated; no Vue/runtime errors.');
    console.log(`Screenshots: ${artifacts}`);
} catch (error) {
    console.error(serverOutput.slice(-2000));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
}
