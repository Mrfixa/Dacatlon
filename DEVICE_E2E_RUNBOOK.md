# Device / Emulator End-to-End Runbook

Full-stack manual E2E for Vito (vitoride / virosend / vitomart). Run on a machine with
**hardware virtualization (KVM / Intel HAXM / Apple Silicon)** — the CI sandbox cannot boot an
emulator, so this pass is done here. CI (`.github/workflows/vito-ci.yml`) already proves compile +
the `VitoFlowTest` journey suite + both apps' analyze/test/APK on every PR to `main`; this runbook
is the on-screen UX + live-integration layer CI can't cover.

Pinned toolchain: **Flutter 3.44.0** (matches CI), PHP 8.2+, Composer 2, Redis, MySQL 8 (or SQLite
for a quick spin). Android SDK w/ an API 34 system image, or a physical device.

---

## 0. Prereqs (one-time)

```bash
# Flutter (matches CI pin)
git clone https://github.com/flutter/flutter.git -b 3.44.0 ~/flutter
export PATH="$HOME/flutter/bin:$PATH"
flutter doctor

# Android SDK + an accelerated AVD (needs KVM: `ls /dev/kvm`)
sdkmanager "platform-tools" "platforms;android-34" "system-images;android-34;google_apis;x86_64" "emulator"
avdmanager create avd -n vito -k "system-images;android-34;google_apis;x86_64" -d pixel_6
emulator -avd vito -gpu swiftshader_indirect &   # drop -gpu on a KVM host for speed
adb wait-for-device
```

---

## 1. Backend up (Laravel)

```bash
cd drivemond-admin-new-install-3.1
composer install --ignore-platform-reqs
cp .env.example .env
php artisan key:generate && php artisan passport:keys --force

# .env: point DB at MySQL (or DB_CONNECTION=sqlite + touch database/database.sqlite),
# set QUEUE_CONNECTION=redis, BROADCAST_DRIVER=reverb, and REVERB_*/PUSHER_* to matching
# non-empty values (PUSHER_APP_KEY drives the apps' websocket_key). For a LAN emulator,
# APP_URL / REVERB_HOST = your host LAN IP (NOT 127.0.0.1 — the emulator can't reach that).

php artisan migrate --seed          # DefaultUsersSeeder: see creds below
php artisan reverb:start --host=0.0.0.0 --port=8080 &     # websocket (chat + live track)
php artisan queue:work &            # RideTimeoutJob auto-cancel
php artisan serve --host=0.0.0.0 --port=8000 &

# smoke: unauthenticated health probe
curl -s http://localhost:8000/api/health   # -> {"status":"ok",...}
```

**Seeded dev logins** (local/non-prod only): customer username `customer` / PIN `123456`;
driver username `driver` / PIN `123456` (pre-approved); admin `admin@admin.com` / `12345678`.
A QR/invite token is required to *register* a new PIN user — generate one in the admin panel
(**QR Invitation Tokens**) or reuse the seeded `customer`/`driver` to skip the gate.

---

## 2. Run both apps against that backend

`BASE_URL` must be reachable from the emulator: use your host LAN IP (e.g. `http://192.168.1.10:8000`),
or `adb reverse tcp:8000 tcp:8000` + `adb reverse tcp:8080 tcp:8080` and use `http://localhost:8000`.

```bash
# Customer app
cd drivemond-user-app-3.1/HexaRide-User-app-release-3.1
flutter pub get
flutter run \
  --dart-define=BASE_URL=http://192.168.1.10:8000 \
  --dart-define=MAPS_API_KEY=<google-maps-key> \
  --dart-define=STRIPE_PUBLISHABLE_KEY=<pk_test_...> \
  --dart-define=MAPBOX_ACCESS_TOKEN=<optional>

# Driver app (2nd emulator or device). NOTE: pubspec pins open_file_plus to a GitHub
# commit — pub get needs network to github.com. Same dart-defines (no STRIPE key needed).
cd drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1
flutter pub get
flutter run --dart-define=BASE_URL=http://192.168.1.10:8000 --dart-define=MAPS_API_KEY=<key>
```

Firebase/push are optional locally: without `--dart-define=FIREBASE_*` the app uses the committed
default project; push delivery needs a real `google-services.json` / APNs. Chat + live tracking use
Reverb (step 1), not FCM, so they work without push.

---

## 3. Journey checklist — verify every state (shimmer / empty / error / success)

Run each with airplane-mode toggles to hit the error/offline states (offline banner + retry).

### A. Onboarding / auth (vitoride entry)
- [ ] First launch → language select (EN/ES) → onboarding slides.
- [ ] QR gate: scan/enter invite token; expired/used token shows a specific error, not a generic one.
- [ ] Register: username + 6-digit PIN + confirm-PIN (mismatch caught client-side).
- [ ] Login seeded `customer`/`123456`; wrong PIN 5× → lockout message; forgot-PIN OTP flow.

### B. Book a ride (vitoride)
- [ ] Home map: current location, nearby drivers; permission-denied path shows prompt not crash.
- [ ] Set pickup/destination (map pin drag re-estimates fare); scheduled-time can't be in the past.
- [ ] Fare estimate per vehicle category; confirm dialog before submit (no double-book on double-tap).
- [ ] State machine on-screen: searching → assigned → **arrived** banner → on_trip → completed.
- [ ] Driver app: receives request, accept (single-winner if two drivers race), "I have arrived",
      start, complete; status buttons disabled while in-flight.
- [ ] Live tracking: driver marker moves (Reverb), camera follows; polyline present.
- [ ] Cancellation → automatic refund to wallet; rating flow after completion.

### C. Send a parcel (virosend)
- [ ] Category + weight (numeric-only), sender/receiver address + who-pays.
- [ ] Fare shown before confirm; submit without a fare estimate does NOT send null coords.
- [ ] Driver accept → pickup OTP → deliver → proof-of-delivery photo; upload failure surfaces an error.
- [ ] Refund request screen: reasons load on open; media picker errors surfaced; size cap enforced.

### D. vitomart (marketplace)
- [ ] Catalog: shimmer → grid; category filter; sort chips (recommended/price/popular); featured/popular shelves.
- [ ] Product details, favorites toggle (optimistic), add-to-cart (badge), reorder from history.
- [ ] Cart: qty change/remove persists; promo apply shows structured error (expired/min-spend);
      changing cart clears an applied promo.
- [ ] Checkout: **server-computed total** (products − promo + tip + delivery_fee), delivery address
      via map picker; wallet balance pre-checked; Stripe 3DS path.
- [ ] Order lifecycle: pending → accepted → picked_up → delivered; tracking poll stops at terminal
      state and on leaving the screen (no setState-after-dispose); ETA shows while out for delivery.
- [ ] Driver: pending list → accept → status → delivery proof + signature; "You earn" line correct
      (delivery_fee + tip + commission); numeric customer_id doesn't red-screen the delivery screen.
- [ ] Mart chat (customer↔driver): send/receive over Reverb; disabled after delivered/cancelled.

### E. Payments
- [ ] Stripe: card requiring 3DS completes; unconfigured gateway is greyed out, not a cryptic fail.
- [ ] Wallet: top-up (Stripe), balance updates; insufficient wallet at mart checkout blocks with shortfall.
- [ ] Webhook idempotency: replaying a Stripe event doesn't double-credit (check `stripe_events`).

### F. Cross-cutting
- [ ] Dark mode across every screen; text scales; 8dp rhythm; no overflow on a small screen.
- [ ] Logout clears profile + cart + Pusher; next login on same device shows no prior-user data.
- [ ] Deep link `vito://invite?token=...` opens the gate.
- [ ] Language switch EN↔ES: no raw `snake_case` keys anywhere (the CI referenced-key test guards this).
- [ ] Kill app mid-ride → relaunch restores ongoing-ride entry point.

---

## 4. What CI already proves (don't re-do by hand)
`VitoFlowTest` (SQLite in-memory): QR token atomicity, PIN/OTP auth + lockout, ride/parcel create
with server-side fare, mart order totals + promo caps + wallet settle/refund, driver earning,
arrived sub-signal, Stripe webhook dedup, forgot-PIN. PHPStan L1. Both apps: analyze + unit/widget
tests + referenced-key parity + debug APK. iOS: both apps compile (`build-ios.yml`).

## 5. Known non-runnable-in-CI (this runbook covers): camera QR scan, real push delivery,
on-screen UX/animation/60fps, live multi-party Reverb sync, Stripe 3DS UI.
