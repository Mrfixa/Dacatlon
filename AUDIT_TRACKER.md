# AUDIT_TRACKER.md — living audit ledger

Single source of truth for end-to-end audit findings across the three sub-projects
(Laravel backend, Flutter user app, Flutter driver app). Each finding is recorded once,
with a stable ID, a severity, the area, the finding, a status, and the fix commit (short SHA).

**Status legend**
- `fixed` — code change landed; Fix column has the commit SHA.
- `accepted` — reviewed and deliberately not changed; the Finding column states why (false positive,
  by-design, or out-of-scope per the wave boundaries).
- `open` — confirmed, not yet fixed.

**ID prefixes** — `B` backend, `U` user app, `D` driver app, `C` cross-cutting / CI.

---

## Findings

| ID | Severity | Area | Finding | Status | Fix |
|----|----------|------|---------|--------|-----|
| B1 | High | Backend / TripManagement (safety alert) | Customer & Driver `SafetyAlertController` `resend`/`markAsSolved`/`show`/`delete` looked up the alert by `trip_request_id` + `user_type` only — **not** scoped to the authenticated user. Any authenticated customer/driver could view, resend, resolve, or delete another user's panic-button alert by supplying a different trip UUID (IDOR). Fixed by scoping every lookup to `sent_by = auth user` (safe because `SafetyAlertService::create` forces `sent_by` to the auth user). | fixed | `e7c5c67` |
| B2 | Medium | Backend / TripManagement (parcel refund) | `ParcelRefundController::createParcelRefundRequest` never verified the authenticated customer owned `trip_request_id`. A customer could open a (pending, admin-reviewed) refund and push a "amount deducted" notification to the driver against an arbitrary parcel trip by UUID. Added a `TripRequest where id + customer_id = auth` ownership guard (404 otherwise). | fixed | `e7c5c67` |
| B3 | High | Backend / AuthManagement (registration) | Legacy self-registration passed `$request->all()` straight into `customerService/driverService->create`, allowing mass-assignment of privileged `User` columns (`loyalty_points`, `role_id`, `user_level_id`, …) that aren't in the register validation rules but are `fillable`. Replaced with `$request->except([...privileged list...])` at both register call sites. | fixed | `7cedf07` |
| B4 | Medium | Backend / config | `env('APP_MODE')` is read at ~100 legacy call sites; under `php artisan config:cache` the `.env` is no longer loaded so these return `null`, which (e.g.) forces trip/parcel OTPs to the demo value `'0000'` in a cached prod deploy. Captured `app.app_mode` in `config/app.php` and re-hydrated `putenv`/`$_ENV`/`$_SERVER` in `AppServiceProvider::register()`. | fixed | `2a9e817` |
| U1 | Medium | User app / chat | Chat attachment file-name rendering used `substring(fileName.length - 7)` with no length guard → `RangeError` crash on names shorter than 7 chars. Replaced with a length-guarded ternary. | fixed | `ec6d46a` |
| D1 | Medium | Driver app / auth | Verification screen masked the phone with an unguarded substring → `RangeError` on short numbers. Now `number.length >= 8 ? mask : number`. | fixed | `ec6d46a` |
| D2 | Medium | Driver app / chat | Same unguarded `substring(fileName.length - 7)` crash in the admin-conversation bubble. Length-guarded. | fixed | `ec6d46a` |
| D3 | Medium | Driver app / chat | A *third* unguarded `substring(fileName!.length - 7)` (missed by D1/D2) survived in `features/chat/widgets/message_bubble_widget.dart` — `RangeError` crash when a chat attachment file name is shorter than 7 chars. Replaced with the same guarded ternary used by the user app's `message_bubble.dart`. | fixed | `0d2fc9a` |
| D4 | Med (UX) | Driver app / vehicle form | Vehicle **brand / model / category** used plain `DropdownButton`s — long, scroll-only, unsearchable lists. Converted all three to a reusable `SearchableDropdownField` (type-to-filter via `flutter_typeahead`): typing a brand filters to matching brands; selecting a brand loads only that brand's models; typing a model filters to matching models. Localized in en/es. | fixed | `927d885` |
| C1 | Medium | CI / iOS | `build-ios.yml` failed at `flutter create --platforms=ios` (`Failed to copy plugin firebase_messaging … build/ios/SourcePackages … No such file or directory`). Flutter 3.29+ enables Swift Package Manager by default; the implicit `pub get` rsyncs the Firebase plugin into a not-yet-created `SourcePackages` dir and aborts. Disabled SPM (`flutter config --no-enable-swift-package-manager`) so the build stays on CocoaPods. | fixed | `9c76e78` |
| C2 | Medium | CI / iOS | After C1, `pod install` failed: `mobile_scanner` (user) and `google_mlkit_commons` (driver) require iOS ≥ 15.5 but the Runner target was 15.0. Bumped the Podfile platform + post_install **and** the Runner `IPHONEOS_DEPLOYMENT_TARGET` (Flutter validates each plugin's minimum against the app target) to 16.0. | fixed | `4e858ee` |
| C3 | Med | CI / iOS | After C2 the **user** app reached the Xcode build but both apps hit `connectivity_plus 7.2.0` using `NWPath.isUltraConstrained` (iOS 18 SDK only) — the macos-14 Xcode was too old. Moved the runner to `macos-15` and pinned the newest Xcode via `setup-xcode` (`latest-stable`) to get the iOS 18.4+ SDK that defines `isUltraConstrained`. **Verified:** the user-app iOS job builds green (run 28352365183); only the driver job remains, isolated to C4. | fixed | `209496a` |
| C4 | Med | CI / iOS (driver) | Driver `pod install` couldn't resolve ML Kit: `google_mlkit_commons` needs `MLKitVision ~>10` while `mobile_scanner 6`→`GoogleMLKit/BarcodeScanning 7.0`→`MLKitVision ~>8` (non-overlapping). Fixed by bumping `mobile_scanner` to `^7.0.0`, which uses **Apple Vision** on iOS and drops the GoogleMLKit pod entirely — removing one side of the conflict. The QR scanner screen uses only APIs unchanged in 7.x (`facing`/`detectionSpeed`/`onDetect`/`analyzeImage`/`toggleTorch`), so no Dart changes were needed. | fixed | `90c0425` |

| U2 | Med (i18n) | User app / localization | Removed **Arabic** entirely (product decision): dropped the `ar` `LanguageModel` and `assets/language/ar.json`, trimmed the parity test to en/es, and added a `localization_controller` fallback so a previously-stored `ar` locale resets to `en` (otherwise the app would render RTL with English fallback text after upgrade). | fixed | `c099038` |

| U3/D5 | Med | Both apps / crash surface | `PriceConverter` ran `int.parse(currencyDecimalPoint)` on **every price render** — a non-numeric server config crashed the app app-wide. Added `lib/util/parse_utils.dart` (`toDoubleOr`/`toIntOr`/`toIntOrNull`, accepting null/String/num) and applied it on the genuine crash-path fields: `PriceConverter` (both apps, clamped 0–20), user `config_model` startup radii, and `referral_details_model`. Helpers unit-tested in both apps. Scope = critical paths only (A2 policy unchanged elsewhere). | fixed | `8df98f4` |

| C5 | Med | CI / iOS (signing) | iOS builds were unsigned (compile-proof only). Added `.github/workflows/release-ios.yml` — a manual-dispatch signed lane (import cert/profile, drop `GoogleService-Info.plist`, inject iOS Maps key, `flutter build ipa`, upload to TestFlight) + `ExportOptions.plist` + documented secrets in `IOS_BUILD.md`. Dispatch-only, so it never auto-runs/fails; the owner runs it once Apple secrets are added. Not CI-verifiable here. | scaffolded | `7a65d5d` |
| W4 | — | User app / mart (tech debt) | **Partly done.** Step 1 (safe, CI-verifiable): extracted the screen's pure status logic into `mart_order_status.dart` (`martOrderStepIndex`/`isMartOrderTerminal`/`canCancelMartOrder`, single source of truth mirroring backend `STATUS_TRANSITIONS`), screen delegates, unit-tested (`db956d6`). **Remaining/deferred:** the `Timer.periodic` poll loop + connectivity `StreamSubscription` + order/driver `setState` machinery — a large refactor of a critical live flow that CI can only compile-check, so it needs a device/emulator-verified pass before moving it. | partial | `db956d6` |

## VitoMart production-readiness deep audit (M-series)

| ID | Severity | Area | Finding | Status | Fix |
|----|----------|------|---------|--------|-----|
| M1 | **High (money)** | Backend / mart payment | `payment_method='wallet'` orders were created `unpaid` and **never charged** (no wallet-pay route, no debit anywhere) → fulfilled for free. Fixed: debit the wallet atomically at order-create (`lockForUpdate` balance check → `decrement` → `paid`; insufficient → 400 + full rollback), and refund the wallet on cancellation across **all three** paths (customer/driver/admin), in-txn. Test: `test_mart_wallet_payment_settles_and_refunds`. | fixed | `89f0b19` |
| M2 | Medium | Backend / mart refund | Driver/admin cancel of a **card-paid** order never refunded (only customer-cancel did). Fixed: extracted `refundOrderPayment` into a shared `Concerns/RefundsMartOrders` trait used by all three cancel paths (customer/driver/admin), each issuing the Stripe refund **after commit** (no DB locks held during the external call); the in-txn `refund_pending` flag is the trigger, upgraded to `refunded` on success. Verified by the existing `mart cancel paid order runs refund lookup` test. **Minor follow-up (accepted):** splitting `payment_status`/`refund_status` is cosmetic — `refund_pending`/`refunded` already track it. | fixed | `89f0b19`,`75027da` |
| M3 | Low (product) | Backend / mart pricing | No delivery fee / tax. Fixed: `createOrder` adds config-driven `delivery_fee` (flat) + `tax_amount` (`mart_tax_percent` on discounted subtotal) to the server total and stores them (migration + `MartOrder` fillable/casts). Both **default to 0**, so pricing is unchanged until the business sets `mart_delivery_fee`/`mart_tax_percent`; wallet debit charges the full total. Test `test_mart_order_applies_config_delivery_fee_and_tax`. | fixed | `82e8a2c` |
| M4 | Medium | Both apps / mart | Mart push notifications weren't routed in the **customer** app (`notificationRouteCheck` had no mart branch). Fixed: added a `type=='mart'` branch → `MartOrderTrackingScreen` (order id from `ride_request_id`/`order_id`). The **driver** app already routed mart pushes (`new_mart_order`→pending list; status actions→dashboard). Runtime tap-delivery still warrants a device smoke-test (CI compiles only). | fixed | `679136c` |
| M5 | Medium | User app / mart | No "reorder from history". Fixed: `MartController.reorder(order)` re-fetches each item live and re-adds it to the cart honouring current stock/price (skips discontinued/out-of-stock, returns skip count); order-history cards get a Reorder button. en/es localized. | fixed | `587445a` |
| M6 | Medium | Backend+user / mart | **Backend production-ready (verified):** `products` already does server-side `LIKE`-escaped search **and** `->paginate()`. The app's client-side search over the first page is acceptable at current catalog size; full server-search + infinite-scroll UI is a low-priority app enhancement (not retrofitted blind into the heavy store screen). | accepted | — |
| M7 | Low | Backend / mart tracking | Live tracking renders `estimated_arrival` but the server never set it. Fixed: haversine ETA (driver → delivery, ~25 km/h) returned from customer `orderDetails` as "~N min" while out for delivery; test added. | fixed | `a1a7465` |

## GoMart parity (G-series)

| ID | Area | Finding / change | Status | Fix |
|----|------|------------------|--------|-----|
| G1 | Backend / mart pricing | **No tax** (per request): order total = subtotal − promo + tip + delivery_fee; `tax_amount` stays 0. Test updated. | fixed | `4f12995` |
| G2 | Backend + app / discovery | Product `discount_price`/`unit`/`is_featured`/`is_popular`/`sold_count` (migration, casts, `effective_price`); `products` endpoint `sort` (price_asc/desc/popular) + featured/popular filters; orders charge the **sale price** and bump `sold_count`. App `MartProductModel` gains the fields + `effectivePrice`/`onSale`. Backend tested. | fixed | `4f12995`,`0a55491` |
| G3 | Backend + app / favorites | `mart_favorites` table + `MartFavorite` + owner-scoped toggle/list endpoints (tested). App: favorites through all 4 layers + `MartController` (optimistic toggle, `isFavorite`, list) + `MartFavoritesScreen`. | fixed | `4f12995`,`0a55491` |
| G4 | App / store UI | **Done.** Sale-price/strike-through + unit label + Favorites entry (earlier); sort `ChoiceChip` row (recommended/price asc/desc/popular → backend `sort` param through all 4 layers) and Featured/Popular horizontal shelves (`is_featured`/`is_popular` filters) above the grid on the default view; sticky bottom cart bar already existed (`FloatingActionButton.extended` with count+total). Unit-tested (sort/price semantics). | fixed | `4f12995`,`96dfa18` |
| G5 | App / checkout | **Done.** Delivery address now picked via the shared `PickMapScreen` (pin confirm + Places search + current-location FAB, same screen the address book uses) instead of the free-text field that autofilled raw lat/lng; plus a saved-addresses (home/work) quick-pick bottom sheet. Compile-verified; on-device pass still recommended (V1). | fixed | `96dfa18` |
| G6 | Backend + app / availability | **Items are always available** (per request): removed all stock gating. Backend `createOrder` no longer checks/decrements `stock` and cancel paths (customer/driver/admin) no longer restore it (`sold_count` still increments); the `stock` column stays as a non-binding admin field. App: `MartProductModel.inStock` tracks `is_active` only, `addToCart` has no out-of-stock reject / quantity cap, and the store card + product details drop the out-of-stock badge/dim/disabled-add. Backend + Flutter unit tests updated. | fixed | `<this>` |

## Production-readiness wave (P-series, 2026-07-04)

| ID | Severity | Area | Finding / change | Status | Fix |
|----|----------|------|------------------|--------|-----|
| P1 | **High** | Both apps / compile | HEAD did not compile (the P0.2 commit merged without CI, which only runs on master/vlad): broken bracket structure in user `home_screen`/`message_screen`, phantom getters (`vehicleCategoryName`, `selectedCategory`, `Images.appLogo`, `MapboxMap.camera`), wrong `CustomTextField` params, driver `toggleOnlineStatus()`/`convertPrice` misuse. All repaired; `flutter analyze` 0 errors both apps. | fixed | `4de8bc8` |
| P2 | **High** | Both apps / i18n | Language JSON was unparseable (double commas, a mangled `sound_vibration` line) and EN/ES keys diverged (11 EN-only, 3 ES-only) — localization would fail to load at runtime. Repaired + missing translations added both sides. | fixed | `f0de3b6` |
| P3 | High | Backend / seeding | `AdminUserSeeder` seeded `admin@admin.com`/`12345678` unconditionally (prod incl.). Now requires `ADMIN_SEED_EMAIL`/`ADMIN_SEED_PASSWORD` (≥12 chars) in production; wallet seeder no longer crashes when admin absent. | fixed | `91be549` |
| P4 | High | Backend / auth | No Passport token expiry (tokens lived forever). Now 30d access / 60d refresh, env-overridable. | fixed | `91be549` |
| P5 | **High (money)** | Backend / Stripe webhook | `env('STRIPE_WEBHOOK_SECRET')` read at runtime — null under `config:cache`, so the webhook would reject all events in a cached-config prod deploy (secret "not configured"). Moved to `config('services.stripe.webhook_secret')`. Found by the new larastan rule. | fixed | `<this>` |
| P6 | Med | Backend / promo preview | App `MartController.applyPromo` sent `order_total` (backend expects `subtotal`) and read the discount at the wrong response depth — the controller-side promo path could never succeed. Fixed with the cart-screen migration; cart mutations now auto-clear an applied promo. | fixed | `4b3da93` |
| P7 | Med | User app / cart | Cart qty/dismiss mutated the passed-in list directly, bypassing SharedPreferences persistence and controller listeners. Routed through `updateCartItemQuantity`/`removeFromCart`; cart body is a `GetBuilder`. | fixed | `4b3da93` |
| P8 | Med | Security / supply chain | `open_file_plus` git override pinned to an immutable commit; Swish TEST certs untracked; `.env.example` broadcast ids/keys blanked; Firebase config dart-define-able (both apps + SW snapshot blanked, panel regenerates it); Supervisor+systemd worker units committed. | fixed | `91be549` |
| P9 | Med | CI | `BASE_URL` now injected into every Flutter build step (was silently defaulting to the live host); `workflow_dispatch` added so CI can run on feature branches; PHPStan raised to level 1 + larastan via committed `phpstan.neon` (relation return types added to `MartOrder`/`MartFavorite`/`TripRequest`/both `User` models). | fixed | `234bf5b`,`7e61247`,`<this>` |
| P10 | Med | Backend / tests | Legacy OTP auth path now covered: existing-user OTP login (200+token), new-phone 406, 30s resend cooldown (429), 5-attempt lockout (422). | fixed | `<this>` |
| P11 | Low | User app / trips | Trip history free-text search (ref id + addresses) over loaded pages, alongside the status tabs. | fixed | `96dfa18` |
| P12 | — | W4/M3 closure | Mart screens now source data/mutations from `MartController` (store catalog dedupe + live connectivity offline banner; cart/promo/checkout data; payment-intent + cancel(+reason) + review through the 4 layers; driver delivery screen was already migrated). Remaining `setState` is per-screen view state (loading flags, tip selector, proof capture) — deliberate; poll/Pusher lifecycles untouched per the no-device constraint. | fixed | `4b3da93` |

## Gap-audit wave 2 (Q-series, 2026-07-04)

| ID | Severity | Area | Finding / change | Status | Fix |
|----|----------|------|------------------|--------|-----|
| Q1 | **High (money)** | Backend / mart driver pay | Mart deliveries credited the driver **nothing** — no earning column, no wallet credit on `delivered`, no payload field (rides credit drivers; mart didn't). Drivers delivered for free and admin had no payout record; the customer's tip also went nowhere. Fixed: added `driver_earning` to `mart_orders`; the `delivered` branch of `VitoMartDriverController::updateStatus` now credits the driver's wallet atomically with `delivery_fee + tip_amount + mart_driver_commission_percent%` of total (commission default 0) and stores it on the order. Driver app parses `driver_earning`/`delivery_fee` and the delivery screen shows a "You earn" line. Test `test_mart_delivered_credits_driver_earning`. | fixed | `<this>` |
| Q2 | Med | User app / privacy | Mart cart persisted under a global key and survived logout → the next user on a shared device inherited (and could check out with) the previous user's cart. Fixed: logout now calls `MartController.clearCart()`. | fixed | `<this>` |
| Q3 | Low | Backend / mart copy | Driver-cancel push claimed "We are finding another driver" though the order is terminally cancelled with no re-dispatch. Copy now reflects terminal cancellation + refund status. | fixed | `<this>` |
| Q4 | Low | Both apps / orphan P0.2 UI | `EarningsSummaryWidget`, `TripPreferencesScreen`, `NotificationSettingsScreen`, `AppRatingDialog` exist but are never mounted, and the two settings screens have no persistence. **Accepted for now** (harmless dead code; wiring non-persistent toggles would ship fake features, and real backend persistence is out of scope for this release). Per-order driver earning is surfaced directly on the delivery screen instead of via the unmounted earnings widget. | accepted | — |

## God-mode re-audit wave (R-series, 2026-07-05)

Fresh 3-agent verification sweep at HEAD `ff0d9b1` (v3.3.0) + live GitHub Actions inspection.
All money/auth/Stripe/seeder/CORS fundamentals re-confirmed GOOD; the items below were the only
real remaining defects.

| ID | Severity | Area | Finding / change | Status | Fix |
|----|----------|------|------------------|--------|-----|
| R1 | **High (deploy)** | Backend / migrations | Duplicate unguarded `Schema::create('stripe_events')` — root `2024_01_01_000004` (correct UUID schema) **and** module `Gateways/.../2026_06_03_100007` (wrong bigint/NOT NULL schema) → fresh `migrate --force` aborts with "table already exists" (deploy.sh:21). VitoFlowTest hand-builds its schema so tests never caught it. Guarded the module migration with `Schema::hasTable` (same pattern as the earlier `vito_otps` dup). | fixed | `<this>` |
| R2 | **High (runtime)** | Backend / config API | Customer+Driver `ConfigController` read `env('PUSHER_APP_KEY')`/`env('PUSHER_SCHEME')`/`env('APP_MODE')` on the request path — null under `config:cache` (deploy.sh runs it), so prod clients got `websocket_key=null` (chat/live-tracking auth dead) and `is_demo=true`. Switched to `config('broadcasting.connections.pusher.*')` / `config('app.app_mode')`; pusher scheme default changed http→https (mirrored to apps, which treat non-https as plain ws). | fixed | `<this>` |
| R3 | Med | Backend / queue | `.env.example` shipped `QUEUE_CONNECTION=database` while the committed worker units ran `queue:work redis` — RideTimeoutJob enqueued on one connection, drained from another → unaccepted rides never auto-cancelled. `.env.example` now `redis`; units use the default connection so they can't diverge again. | fixed | `<this>` |
| R4 | Low | Backend / trips | Re-trip fare inheritance looked up the source trip by id only; now scoped to `customer_id = auth` (403 otherwise), closing the cross-customer fare-figure read. | fixed | `<this>` |
| R5 | **High (CI)** | CI | `vito-ci.yml` triggered on `[master, vlad]` — branches that don't exist on this repo (default is `main`), so the PHPStan/PHPUnit/Flutter gate **never ran on any push or PR**. Now triggers on `main` too (push + PR); `ui-goldens.yml` likewise. | fixed | `<this>` |
| R6 | Med (CI) | CI / release | `build-apk.yml` was red on every `main` push: legacy `build-driver-ios`/`build-user-ios` simulator jobs lacked the SPM disable (the exact C1 bug, verified in runs 28705767352/28709175264) and duplicated `build-ios.yml`. Jobs removed. `create-release` had no ref guard (any listed branch push could overwrite the current release) and downloaded artifacts this workflow never produces — guarded to tags/main/dispatch, dead steps dropped, stale `claude/*` triggers dropped. `vito-ci.yml` also passed the Stripe key as `STRIPE_KEY` while the app reads `STRIPE_PUBLISHABLE_KEY` — renamed. | fixed | `<this>` |
| R7 | Med (UX) | User app / i18n | 24 code-referenced `.tr` keys missing from **both** en/es (parity test blind spot): token-gate camera-permission strings, ride confirm dialog (`confirm_ride_request`/`pickup`/`vehicle_type`/`estimated_fare`), sign-up `confirm_pin`/`pin_mismatch`, ride-controller validation snackbars, notification settings, +7 minor. All added (EN+ES). New test asserts every `'key'.tr` in `lib/` exists in `en.json` — closes the blind spot permanently. | fixed | `<this>` |
| R8 | Med (UX) | Driver app / i18n | Same scan on the driver app: 29 missing keys (job-request modal `new_ride_request`/`pickup`, QR scanner gallery strings, `yesterday` date label, file-validation messages, mart `items`, + the unmounted trip-preferences screen set). All added (EN+ES) + same referenced-key test. | fixed | `<this>` |
| R9 | Med (crash) | User app / mart | `mart_order_tracking_screen` poll called `setState` after `await` with no `mounted` guard → "setState() called after dispose()" when navigating away mid-poll (15s cadence). Guard added. | fixed | `<this>` |
| R10 | Med (crash) | Driver app / mart | `mart_delivery_screen` cast raw order JSON with `as String?` (`customer_id`/`phone`/`notes`) — numeric JSON red-screens the build. Coerced via `?.toString()`; item price line also used a hardcoded `$` instead of `PriceConverter` (wrong symbol for non-USD) — fixed. | fixed | `<this>` |
| R11 | Low | Deploy | Reverb existed only as a DEPLOY.md heredoc — an operator installing the committed units got no websocket process (chat/tracking dead). Committed `deploy/supervisor/vito-reverb.conf` + `deploy/systemd/vito-reverb.service`; PRODUCTION_DEPLOYMENT.md now covers worker/Reverb/scheduler. | fixed | `<this>` |
| R12 | Low | Docs | `CODEBASE_MAP.md` still claimed `ar.json` "missing/required" (Arabic was removed by product decision); stale B15 out-of-stock comment in `mart_store_screen`. Both cleaned. | fixed | `<this>` |
| R13 | — | Audit hygiene | Prior-audit claim "GET /api/health & /api/admin/metrics don't exist" re-checked and **dismissed** — both live in `TripManagement/Routes/vito_api.php:67,71` (`VitoSystemController`). | accepted | — |
| R14 | Low (perf) | Backend / trips | `trip_requests` shipped with zero indexes (bare `foreignUuid`, no `constrained()`): added guarded `(current_status, zone_id)` + `customer_id` + `driver_id` indexes. Release builds now ship `--obfuscate --split-debug-info` (APK + IPA) with symbols kept as 90-day CI artifacts. | fixed | `22a46ee` |
| R15 | — | Product decisions (owner-confirmed 2026-07-05) | Gojek-template items intentionally absent: **stock reservation** (G6 "always available" stands), **Bloc/Riverpod** (GetX mandated by CLAUDE.md), **certificate pinning** (cert-rotation brick risk vs. Let's Encrypt host), **fastlane** (Actions already builds/signs/releases). **Sentry-in-apps dropped on verification**: both apps already route framework/dispatcher/zone errors to Firebase **Crashlytics** (`main.dart`) — adding sentry_flutter would double-report; backend Sentry remains (set `SENTRY_LARAVEL_DSN`). | accepted | — |

## Wave 14 — end-to-end UX/flow audit of the v3.3-v3.5 surface (X/MB-series)

Full UX + flow audit of both apps covering everything shipped since Wave 13 (VitoMap migration,
mart controller refactor, vehicle searchable dropdowns, trip search). Fix commit: see the
"wave 14" commit on this branch.

| ID | Severity | Area | Finding | Status |
|----|----------|------|---------|--------|
| X1 | **High (money)** | User app / mart | `createOrder` rotated the idempotency key on **every** failure, incl. transport timeouts where the order may have reached the server -> retry = duplicate order/double charge. Now the key is kept on transport (status 0/1) and 5xx failures and on 2xx-without-order-id (backend replays the cached 2xx, dedup holds); only a definitive 4xx or success rotates. Unit-tested (4 cases). | fixed |
| X2 | Medium | Driver app / mart | Pending-orders 15s auto-refresh (`getPendingOrders(notify:false)`) replaced the list but never called `update()` -> new orders invisible until pull-to-refresh. `notify` now only suppresses the spinner; the UI always rebuilds after fresh data. Same fix in `getMyOrders`. | fixed |
| X3 | Medium | Driver app / vehicle form | Brand with null `vehicle_models` threw (force-unwrap); empty list left a required model field with only "no_match_found" -> registration dead end. Null-guarded + the screen now shows a localized hint pointing at the "Other" brand when a brand has no models. | fixed |
| X4 | Medium | Driver app / vehicle form | Typing an exact brand/model/category name without tapping the suggestion row left the placeholder selected ("Toyota" visible, "select vehicle brand" error). `SearchableDropdownField` now commits an exact (case-insensitive) text match on submit/tap-outside; redundant re-commits guarded so a re-focused brand field can't wipe a chosen model. Unit-tested. | fixed |
| X5 | Low | User app / mart | Disabled "Cancel order" + "in transit" copy rendered even on delivered/cancelled orders; now hidden on terminal statuses. | fixed |
| X6 | Low | User app / mart | Unknown backend promo errors surfaced as raw English strings on ES devices; fallback is now the localized generic error. | fixed |
| X7 | Low | User app / trips | Search filters only loaded pages; a helper text now says so while a query is active (server-side search is a backend feature gap, not retrofitted). | fixed |
| X8 | Low | Driver app / status | `profileOnlineOffline(bool value)` ignored its argument and blind-toggled off local state; the backend endpoint is itself a toggle, so a stale tap could flip the driver the wrong way. Now a no-op when local state already matches the requested target. | fixed |
| X9 | Low | Driver app / mart | Same Idempotency-Key header on proof-upload and delivered-status calls: **verified benign** - backend `IdempotencyKey` middleware scopes the cache to key+user+**path**, so different endpoints never collide. | accepted |
| X10 | Low | Driver app / vehicle models | `json['is_active'] ? 1 : 0` threw on int/null; one malformed row blanked the whole brand list. Tolerant cast (`bool`/`num`/string). Unit-tested. | fixed |
| MB1 | High (Mapbox mode) | Both apps / maps | Screens/controllers grabbed `vitoController.googleController` (null on Mapbox): crashes in add-address & search-and-pick (`mapController!.moveCamera`), swallowed `bounds!` throw in driver ride map, `getVisibleRegion` on null in out-of-zone; route auto-fit/recenter/driver-follow silently dead across ~12 sites. All camera plumbing now typed `VitoMapController` (unified `animateCamera`/`moveCamera`/`animateToLatLng`/`fitBounds`); `zoomToFit` keeps the legacy Google loop verbatim and uses `fitBounds` on Mapbox. Also fixed a vendor self-assignment bug (`setMapController` never set the field, driver) and dead `_controller` camera-follow (driver map screen). | fixed |
| MB2 | High (Mapbox mode) | Both apps / maps | Every legacy `googleMarkers` marker rendered as the same generic red pin on Mapbox -> pickup/destination/driver indistinguishable. Default pins are now colour-differentiated by marker id vocabulary (driver/rider/car = blue, from/pickup/home = green, my_location = teal, destination/other = red). | fixed |
| MB3 | Medium (Mapbox mode) | Both apps / maps | `didUpdateWidget` compared fresh `Set` instances (always !=) -> deleteAll+recreate of all Mapbox annotations on every GetBuilder rebuild (flicker, racing syncs). Now a cheap change-signature dirty-check plus a serialize-latch around annotation syncs. | fixed |
| MB4 | Medium (Mapbox mode) | Both apps / maps | `googlePolygons` (zone shading) not rendered on Mapbox -> driver out-of-zone screen lost the shaded zone. Polygons are now mirrored as Mapbox polygon annotations (fill + outline, alpha via fillOpacity). | fixed |

**Verified clean this wave (no change needed):** mart cart/promo/order flow (M15/M16 fixes hold:
promo cleared on every cart mutation, cart preserved on failed order), cancel gating matches
`STATUS_TRANSITIONS`, tracking-screen disposal (timer/connectivity/Pusher), logout cart clear,
driver delivery proof offline persistence/restore, EN/ES i18n exact parity in both apps, Google
(default) provider path regression-free, auth journeys (token gate -> QR -> signup -> OTP) intact.

## Wave 15 — end-to-end backend + admin panel audit (BK/AD-series)

Two read-only sweeps (backend API surface; admin web panel) over everything post-Wave-13. All
confirmed findings fixed. Money/auth/Stripe cores re-verified: no CRITICAL.

### Backend API (BK)
| ID | Severity | Area | Finding | Status |
|----|----------|------|---------|--------|
| BK1 | Medium (money) | TripManagement / mart | Card mart orders were `pending`+`unpaid` yet dispatchable — driver could accept + deliver + get wallet-credited for an order never paid (no card-on-delivery capture). `pendingOrders` and `acceptOrder` now exclude `payment_method=card` unless `payment_status=paid`; cash (pay-on-delivery) and wallet (debited at create) still fulfil immediately. | fixed |
| BK2 | Medium | AuthManagement / OTP SMS | Twilio fallback creds read via `env()` on the request path → null under `config:cache`, silently killing OTP delivery. Moved to `config('services.twilio.*')` (added the block to `config/services.php`). | fixed |
| BK3 | Medium | BusinessManagement / MapProviderService | Every provider HTTP call used Laravel's 30s default timeout with no try/catch — a provider slowdown blocked FPM workers and 500'd geocode/autocomplete. Added `timeout(6)/connectTimeout(3)` to all 11 calls and wrapped each public op in try/catch returning the app-parseable empty fallback. | fixed |
| BK4 | Low | TripManagement / health | Unauthenticated `/api/health` echoed raw DB/cache exception messages. Now returns generic `'error'`, detail to log only. | fixed |
| BK5 | Low | BusinessManagement / ConfigController | `origin/destination lat/lng` + `lat/lng` validated `required` only; concatenated unencoded into the outbound Google URL. Added `numeric|between` bounds (customer + driver). | fixed |
| BK6 | Low | Gateways / stripe routes | `payment-intent` / `order-payment-intent` had `idempotent` but no throttle (spam distinct live PaymentIntents). Added `throttle:10,1` to match peers. | fixed |
| BK7 | Low | AuthManagement / QR API | `generateToken` API defaulted length 16/32, but `pinRegister` requires `size:64` → API-issued invites un-redeemable. Default + floor the length at 64. | fixed |
| BK8 | Low | VehicleManagement / routes | Driver + customer vehicle catalog routes lacked `scope:` middleware (cross-scope reachable). Added `scope:AccessToDriver` / `scope:AccessToCustomer`. | fixed |
| BK9 | Low | app / IdempotencyKey | Check-then-execute with no lock: same-key double-tap within the window ran twice. Added a `Cache::lock` (block ≤10s, fail-open) with a post-lock cache re-check. | fixed |
| BK10 | Low | AuthManagement / OTP attempts | 5-attempt cap was read-check-increment (concurrent verifies over-granted). Replaced with an atomic conditional `where('attempts','<',5)->increment` in both OTP verify paths. | fixed |

**Verified OK (backend):** Stripe webhook idempotency (stripe_event_id unique + lock + status re-check), server-side order-amount recompute, mart wallet debit/refund atomicity, promo used_count + per-user caps under lock, mart status machine replay-safety, PIN auth lockout + token revocation, scope middleware on mart routes, vehicle seeder idempotency, tip 30% cap, (0,0)-coordinate rejection, escaped LIKE search.

### Admin panel (AD)
| ID | Severity | Area | Finding | Status |
|----|----------|------|---------|--------|
| AD1 | High | BusinessManagement / config controllers | Payment/SMS/AI/Login/FirebaseOtp config controllers stored secrets with **no** `authorize()` and only the `admin` middleware (no per-module permission) — any employee role could read/overwrite live gateway keys via direct URL. Added `business_view`/`business_edit` gates matching the sibling `ThirdPartyController` (+ two ungated `NotificationController` methods). | fixed |
| AD2 | Medium | TripManagement / mart export | CSV/XLSX export wrote user-controlled names/promo unescaped → spreadsheet formula injection; and `->get()` was unbounded + ignored the date range. Added `csvSafe()` (`'` prefix on `=+-@`), a 20k-row cap, and the date-range filter. | fixed |
| AD3 | Low | TripManagement / promo admin | Percent promo could be saved >100% (bounded to free by the compute clamp, but no warning). `discount_value` now `max:100` when `discount_type=percent`. | fixed |
| AD4 | Low | TripManagement / mart dashboard | "Top products" ignored the selected date range (always all-time). Joined `mart_orders` and applied the `from/to` filter. | fixed |
| AD5 | Low | TripManagement / mart categories | Category delete didn't check for products still referencing it by name → stranded, unfilterable products. `destroy` now blocks with a count when products are attached. | fixed |

**Verified OK (admin):** all VitoMart controller methods gate on the correct `vito_mart_*` permission; no GET-based state changes in the mart group; Google Map form gated + provider whitelisted + keys not echoed; mart blades escape all user data with `{{ }}`; all `admin.mart.*` route names resolve; admin cancel refund path atomic; order list eager-loaded (no N+1).

## Accepted (reviewed, intentionally not changed)

| ID | Severity | Area | Finding & rationale | Status |
|----|----------|------|---------------------|--------|
| A1 | Low | Both apps / Firebase | `AIzaSy…` Android API key hardcoded in `lib/main.dart`. This is the Firebase **Android** API key, which is public-by-design (restricted by SHA-1 + package name in the Firebase console, not a secret). No action. | accepted |
| A2 | Low | Both apps / null-safety | ~286 `!.data!` force-unwraps across both apps. Sampled on the critical paths (payment, order create, auth) — the overwhelming majority are immediately preceded by a null/`isEmpty`/`statusCode` guard. Blanket-rewriting them is churn with regression risk and no test coverage; left as-is. Genuinely unguarded crash sites are tracked individually (e.g. U1/D1/D2). | accepted |
| A3 | Low | Backend / FareManagement | `TripFareController@store` uses `$request->all()`. It is an **admin-only** web controller (already-privileged actor, no privilege escalation surface) and the `TripFare` model has no sensitive columns. Not user-reachable. | accepted |
| A4 | Info | Backend / config | The ~100 `env('APP_MODE')` runtime call sites are not individually rewritten; B4's provider re-hydration makes them all correct under both cached and non-cached config without touching each site. | accepted |
| A5 | Info | Backend / Vito API | IDOR sweep of the customer/driver Vito surface: mart **products** are a public catalog (`MartProduct::find` is intentionally unscoped); mart **orders**, rides, and parcels are owner-scoped in their services. Safety alert (B1) and parcel refund (B2) were the gaps and are fixed. | accepted |

---

## How findings are verified

- **Backend:** `php artisan test --filter=VitoFlowTest` (124 passing) stays green after each change;
  PHPStan level 0 clean on the edited controllers. `php -l` on every edited PHP file.
- **Flutter:** no local Flutter/macOS runner — changes are verified by the CI debug-APK build
  (`vito-ci.yml`), which compiles the edited widgets/screens, plus `flutter analyze` and unit tests.
- **iOS:** the `build-ios.yml` macOS workflow builds an unsigned release `.app` for both apps.

### End-to-end run — last verified at HEAD `f4a7fc8`

| Layer | How it's "simulated/emulated" here | Result |
|-------|-------------------------------------|--------|
| Backend API flows (QR/auth/ride/parcel/mart/Stripe/wallet) | `php artisan test --filter=VitoFlowTest` (SQLite in-memory) | ✅ 124 passed / 379 assertions |
| User + Driver apps (analyze, unit/behavior tests, Android APK) | `vito-ci.yml` run #75 | ✅ success |
| User + Driver apps (iOS build, incl. mobile_scanner 7.x) | `build-ios.yml` run #7 | ✅ success (both jobs) |

### Standing caveats — NOT verifiable in this environment (owner action)

These cannot be proven by local tests or CI (no device/emulator, no Apple secrets). They are *not*
defects — they are the boundary of what's checkable here, recorded so nothing is lost:

| ID | Item | Why it needs owner verification |
|----|------|--------------------------------|
| V1 | On-device/emulator UI run of either app | No Flutter SDK or emulator in this container; CI proves compile+test+APK/IPA only, not live UI. |
| V2 | QR scanner camera scan behavior (driver, `mobile_scanner` 7.x → Apple Vision on iOS) | Camera/scan is runtime-only; `extractToken` logic is unit-tested but the capture path needs a device. |
| V3 | Push notifications on iOS | Firebase no-ops on iOS until `GoogleService-Info.plist` + APNs key are added. |
| V4 | Signed iOS → TestFlight (`release-ios.yml`) | Manual-dispatch; runs only once the Apple secrets (see IOS_BUILD.md) are set. |
| W4 | Mart-screen → `MartController` migration | Deferred: large refactor of a polling-heavy live flow; needs device verification (tracked above). |

## v3.8.5 — native Android/iOS + integration-seam audit (2026-07-19)

Fresh discovery over the surfaces earlier audits skipped (native app layers, push/realtime/landing
seams). Fixed in this release: invite links end-to-end (landing page targeted a non-existent
`com.vito.app` package and an unregistered `vito://` scheme; apps dropped the `?token=` query from
App Links — now `/locate-user|/locate-driver?token=` opens the token-gate screen pre-validated),
websocket host/port env fallback (apps previously received an empty port unless an admin set a DB
business setting), FCM OAuth token cached 55 s → 3300 s, stale `6amTech` notification-channel
meta-data removed + `vito` channel created at startup (both apps), user app now applies the
`google-services` Gradle plugin, driver app dropped unused `ACCESS_BACKGROUND_LOCATION`/
`FOREGROUND_SERVICE` (kills the Play background-location review), debug-signing + missing-Stripe-key
CI warnings, user iOS `NSLocationAlwaysAndWhenInUse` string + duplicate mic key, pbxproj versions
now track `$(FLUTTER_BUILD_NAME)`/`$(FLUTTER_BUILD_NUMBER)`.

### Deliberate no-fixes (decision record)

| Item | Why left as-is |
|------|----------------|
| `minifyEnabled=false` (both apps) | Enabling R8 without device QA risks runtime breakage (Stripe/Pusher/Firebase reflection). Revisit with a device pass. |
| `aps-environment = development` in entitlements | Not a bug — App Store/TestFlight export rewrites it to `production` via the provisioning profile. |
| Committed Google Maps key fallback in `build.gradle.kts` | It is the owner's own key; the action is restricting it (package + SHA-1) in Google Cloud Console, not removing the fallback. |
| Google map tile key not runtime-switchable | Impossible: the native manifest key is baked at build time. Server-side geocoding/routing keys ARE admin-switchable. |
| Apps pinned to Firebase project `drivevalley-fdb7f` | Owner decision. The admin-uploaded FCM service account MUST belong to this project, or the apps must be rebuilt with the `FIREBASE_*` dart-defines + replaced `google-services.json`/`GoogleService-Info.plist`. |

### New owner actions (beyond the standing caveats above)

| ID | Item |
|----|------|
| V5 | Create an Android upload keystore and set `KEYSTORE_BASE64`/`KEYSTORE_PASS`/`KEY_ALIAS`/`KEY_PASS` repo secrets before any Play Store submission (CI now warns on every debug-signed build). |
| V6 | Restrict the committed Maps key by package name + release SHA-1 in Google Cloud Console. |
| V7 | Set the `STRIPE_PUBLISHABLE_KEY` repo secret (CI now warns when empty). |
| V8 | FCM service account uploaded in the admin panel must belong to Firebase project `drivevalley-fdb7f` (see decision record). |
