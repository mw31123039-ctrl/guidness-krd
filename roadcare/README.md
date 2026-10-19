# RoadCare ? Kurdistan Region

A Laravel 13 application with Vue 3 and Inertia 3 for citizen road-damage reports and government repair management. The original HTML prototypes remain in the sibling `FINAL WORK -backup` directory.

## Run the installed application

From the workspace root, double-click `Start-RoadCare.cmd`, or run:

```powershell
cd .\roadcare
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\start.ps1
```

Open **http://127.0.0.1:8000**. Keep the terminal open while using the application. Stop it with Ctrl+C. Open the Laravel URL rather than the old HTML file or VS Code Live Server.

The PHP dependencies, frontend dependencies, SQLite database, migrations, demo records, and production frontend build have already been installed on this computer. The start script increases PHP upload limits to support the application's 8 MB photo limit.

### Local demonstration accounts

| Workspace | Email | Password |
| --- | --- | --- |
| Citizen | citizen@demo.com | RoadCare123! |
| Government | government@roadcare.test | RoadCare123! |

These are local demonstration accounts. The login screen has buttons to fill either account. The demo seeder refuses to run outside `local` and `testing`; it never creates these known-password accounts in production. Six example reports are clearly identified as demonstration records. Seeding again does not duplicate or overwrite existing reports.

## What works

- Citizen registration, sign-in, sign-out, remember-me sessions, hashed passwords, CSRF protection, and sign-in throttling.
- English and Kurdish Sorani, with session-persisted language selection and right-to-left Sorani layouts.
- Photo uploads from a phone or computer, preview, category, severity, description, governorate, street/landmark, and exact coordinates.
- GPS, manual coordinates, and exact-location import from Waze share links.
- Server-side regional polygon validation; out-of-area coordinates are rejected even if submitted outside the UI.
- Private photos available through an authorized controller; other citizens cannot view a report or its photo. Stored images are re-encoded as JPEG to remove EXIF/location metadata.
- Searchable, paginated reports and database-driven dashboard statistics.
- Government review, department assignment, citizen-visible notes, controlled status changes, and an append-only update history.
- Reviewed regional reports with severity filters and selectable Waze pins. Unreviewed and rejected reports are excluded. Shared location data omits citizen names, contact information, descriptions, and photos.
- Mobile navigation and responsive English/Sorani screens.

The repair workflow is:

```text
Submitted ? Under review ? Repair scheduled ? Repair in progress ? Resolved
                ? Closed without repair
```

Government staff can also return a report to an earlier review/planning stage or reopen a resolved/closed report. Every change requires a note. A stale status is rejected if another official has changed the report.

Updates appear in the citizen's report history. Email/SMS notifications, AI image analysis, automated routing, and integration with an actual government agency are not implemented.

## Waze maps and exact locations

Waze Live Map is embedded on the dashboard, regional map, report form, and report detail screens. No Google Maps API key is needed.

To report a location:

1. Use **Find location in Waze** to find the exact road segment.
2. Select **Share** in Waze and copy the full location link.
3. Paste it into **Waze location share link** and press **Use location**.
4. Check the selected Waze pin and populated latitude/longitude before sending the report.

GPS and manual coordinates are also available. Browsing, clicking, or panning within the Waze iframe alone does not update RoadCare's saved coordinates: Waze's documented embed has no coordinate-selection callback to the parent page.

The importer accepts full HTTPS Waze links with explicit coordinates: `ll=latitude,longitude`, directions destinations such as `to=ll.latitude,longitude`, and Waze embed links with `lat`/`lon`. For directions links, the destination takes precedence over the map center or origin. Address-only, place-ID-only, and shortened links without explicit coordinates prompt the user to copy a full location link or use GPS. They are not guessed or fetched by the server.

The regional page supports report selection from its list or dropdown. Waze displays one selected report pin at a time. RoadCare does not overlay custom hazard markers onto Waze or submit reports to Waze. **Navigate with Waze** opens the saved report location in Waze.

RoadCare's controls and help support English and Kurdish Sorani; the embedded Waze interface uses Waze's available languages. Waze requires internet access. An external Waze link, GPS, and manual coordinates are available if the embed is slow or blocked.

References: [Waze Live Map embedding](https://developers.google.com/waze/iframe), [Waze Deep Links](https://developers.google.com/waze/deeplinks/).

## Install again or on another machine

Requires PHP 8.3+ with PDO SQLite, mbstring, openssl, fileinfo, GD, cURL, DOM, and ZIP; Composer; and a current Node.js version supported by Vite. This computer has PHP 8.5 and Node 24.

From `roadcare`, use Composer to install dependencies. This computer also has a local Composer installation at `../tools/composer.phar`, which is excluded from Git; you can use `php ../tools/composer.phar` in place of `composer` here.

```powershell
composer install
npm.cmd ci
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan migrate --seed
npm.cmd run build
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\start.ps1
```

Copy the environment file and create the database only on a fresh installation. Preserve an existing database and environment file.

For frontend development, run `npm.cmd run dev` in a second terminal. Laravel still serves the application on port 8000; Vite serves the development assets.

## Provision a government account

Public registration always creates a citizen. To create an official account, run on the Laravel server:

```powershell
php artisan roadcare:government official@example.org --name="Road Operations"
```

The command prompts for a password without putting it in shell history, requires 10+ characters with a letter, number, and symbol, and refuses to modify an existing account.

## Verification

```powershell
php artisan test
npm.cmd run test:map
npm.cmd run build
npm.cmd run test:browser
```

The backend suite covers registration/role isolation, login, private photos, upload validation, corrupt images, regional coverage, owner-scoped reports, government filters, allowed status changes, stale changes, citizen-visible updates, map privacy, and Sorani.

The map unit suite checks coordinate import, destination precedence, unsupported/unsafe links, map pins, and navigation URLs. The browser runner isolates Waze iframe responses to test RoadCare without relying on external traffic data. It creates its own temporary SQLite database and photo directory, leaving the application's real reports alone. It checks the full citizen/government workflow, file upload, privacy, language switching, mobile navigation, and horizontal overflow, and saves screenshots to `artifacts/`. It uses installed Microsoft Edge or Chrome on Windows; elsewhere install Playwright Chromium with `npx playwright install chromium`, or set `ROADCARE_BROWSER_PATH` to an installed Chromium executable. Port 8011 must be free. Build before running the browser checks.

## Service-area data

`resources/geo/kurdistan.geojson` is derived from geoBoundaries' Iraq ADM1 release, boundary ID `IRQ-ADM1-90741666`, represented year 2022, pinned release `9469f09`, CC0 1.0. Source metadata is retained in `resources/geo/source.json`.

The selected polygons are Erbil (`IQ-AR`), Duhok (`IQ-DA`), and Sulaymaniyah (`IQ-SU`). The source's Sulaymaniyah geometry includes Halabja; Halabja is separately available in the report governorate selector. This historical dataset defines the prototype service area; it is not a statement of current legal or operational jurisdiction. Replace it with agency-approved current service boundaries before public government use. The server validates against this file. Waze can be browsed outside the service area; the server remains authoritative when a report is submitted.

Source: [geoBoundaries API](https://www.geoboundaries.org/api.html), [pinned source GeoJSON](https://github.com/wmgeolab/geoBoundaries/raw/9469f09/releaseData/gbOpen/IRQ/ADM1/geoBoundaries-IRQ-ADM1.geojson).

## Deployment

Deploy the Laravel application to a PHP-capable host with document root `public/`; it is not a static HTML site. Use a separate production database, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, a production `APP_URL`, and secure session cookies. Run migrations without demo seeding, build assets, provision official accounts, and allow Waze embedding through the site's Content Security Policy.

Allow writes to `storage/` and `bootstrap/cache/`; back up the database and private photos together. Configure PHP `upload_max_filesize=10M` and `post_max_size=12M` and a compatible web-server request-size limit. No public storage symlink is needed for report photographs. The local SQLite setup is for development; configure a supported server database if required for the actual deployment.

Before accepting public reports, the operating agency still needs to approve jurisdiction, account provisioning, citizen privacy/retention rules, and the process for acting on submissions. This working local application is not connected to a live government service.
