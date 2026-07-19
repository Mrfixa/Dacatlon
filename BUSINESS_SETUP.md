# Vito — Business Setup (backend)

Everything the backend needs to present as a configured "Vito" business is now
seeded. Applying it on the live server is **one command** (the seeders are
idempotent — safe to re-run; they `updateOrInsert` existing rows).

## What's seeded

**Business information** (`business_settings`, type `business_information`)
- Name **Vito**, address *Cartagena de Indias, Bolívar, Colombia*
- Contact/support email `info@dacatlon.store` / `support@dacatlon.store`, phone `+57…`
- Currency **COP** (`$`, 0 decimals), country code `+57`, timezone `America/Bogota`
- Header logo `vito-logo.png`, favicon `vito-favicon.png`

**Brand logos** — committed under `public/business/` (tracked) and copied to the
runtime disk `storage/app/public/business/` by the seeder:
`vito-logo.png` (header), `vito-favicon.png` (favicon), `vito-logo-square.png`
(256² app/social). Derived from the Vito app artwork. The admin default logo
(`public/assets/admin-module/img/logo.png`) + `public/favicon.*` were refreshed too.

**Google Maps** (`google_map_api`) — the supplied key
`AIzaSyCKoitvi1c7k_TRdynDVid68qk5W-vosr0` is set for server/android/ios and
`map_provider=google`. Override per-env with `GOOGLE_MAPS_API_KEY` in `.env`.
> ⚠️ This key's Cloud project has **no billing** — Google rejects Maps requests
> (grey tiles / failed geocoding) until you enable Billing + Maps SDK/Geocoding/
> Places/Directions/Distance-Matrix on it (or point `GOOGLE_MAPS_API_KEY` at a
> billed key). See `CREDENTIALS_CHECKLIST.md` A3.

**Test customer** (always seeded, incl. production) — customer app login:
`username: testcustomer` · `PIN: 112233`. (The demo `customer`/`driver` accounts
remain gated to non-prod unless `SEED_DEMO_USERS=true`.)

**Test phone login (predictable OTP)** — the customer app's *phone-number* login
sends a random SMS OTP you can't see without an SMS gateway. For testing, one
configured number gets a fixed code instead (no SMS). Defaults (override in
`.env`):

```
VITO_TEST_OTP_PHONE=+18885550000        # this number only
VITO_TEST_OTP_CODE=123456               # its fixed code
VITO_TEST_OTP_ALLOW_PRODUCTION=false    # must be true to work under APP_ENV=production
```

Log in on the phone-number screen with **`+18885550000`** and code **`123456`**.
The `TestCustomerSeeder` puts that number on the `testcustomer` account, so the
OTP login lands straight in a ready customer. Every other number still gets a
secure random OTP.

**Fail-closed in production (v3.8.3+):** under `APP_ENV=production` the feature
is OFF regardless of the phone setting, unless you explicitly set
`VITO_TEST_OTP_ALLOW_PRODUCTION=true` (then `php artisan config:clear`). So to
use the test login on the live server you must add that flag — and **remove it
before public launch**. Outside production, disable with `VITO_TEST_OTP_PHONE=`
(empty).

## Realtime (websocket) config — v3.8.5+

The apps read the websocket host/port from `GET configuration`. Since v3.8.5 those
values fall back to your `.env` (`PUSHER_HOST`/`PUSHER_PORT`, which mirror
`REVERB_HOST`/`REVERB_PORT`) — so with a correct `.env` realtime works with **no**
admin-panel websocket setting. The admin business settings `websocket_url` /
`websocket_port` still win if set; clear them (or keep them correct) after changing
the env values, and run `php artisan config:clear`.

## Invite links — v3.8.5+

`https://dacatlon.store/landing/?token=<64-char-token>` now works end-to-end: the page
validates the token, then offers "Open in Vito App" (Android intent that opens the
installed customer or driver app by the token's role and pre-validates it on the token
screen) with a Play Store fallback. iOS buttons stay hidden until the iOS apps are
published (`IOS_AVAILABLE` flag in `landing/index.html`).

## Apply on the server

```bash
cd /var/www/vito/drivemond-admin-new-install-3.1
git pull                                    # pulls the seeders + public/business logos
php artisan storage:link                    # if not already linked
php artisan db:seed --class=Database\\Seeders\\TestCustomerSeeder --force
php artisan db:seed --class=Modules\\BusinessManagement\\Database\\Seeders\\BusinessManagementDatabaseSeeder --force
php artisan optimize:clear                  # flush cached config/business settings
```

Then log into the admin panel → **Business → Business Setup** to review, and the
customer app with `testcustomer` / `112233`. Change the seeded contact details,
currency, and test-customer PIN to your real values before public launch.
