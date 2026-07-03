# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Reference Docs — read these BEFORE exploring (token protocol)

This is a large repo (~1 730 backend PHP files, ~860 Dart files). To avoid burning tokens
re-discovering structure, consult these committed index docs first; they are kept current and
usually answer "where is X" without reading source:

| Doc | Use it to find |
|-----|----------------|
| `CODEBASE_MAP.md` | Directory/feature layout of all 3 sub-projects; "Where to Look" task→file table |
| `API_INDEX.md` | Every Vito API endpoint → exact `Controller@method`, scope, throttle, middleware |
| `USER_APP_AUDIT.md` | Known bugs/gaps in the user app (61 findings, severity-ranked) — check before "fixing" |
| `CLIENT_APP_FLOWS.md` | Screen-by-screen flow map of every customer-app journey (auth, ride, mart, parcel, wallet, chat) |
| `AUTH_AUDIT.md` / `VITO_AUDIT.md` / `AUDIT.md` | Prior audit findings + decisions already made |

**Working efficiently in this repo:**
- Resolve a task to specific files via the docs above, then `Read` only those files (use `offset`/`limit` for big files).
- Prefer `Grep`/`Glob` with targeted patterns over reading whole directories. The route files and `CODEBASE_MAP.md` tell you which module/feature owns a concern.
- When you change the API surface, feature layout, or fix an audited gap, update the matching index doc in the same commit so it stays trustworthy.
- Don't spawn Explore/Plan subagents for questions the index docs already answer.

## Repository Overview

Three-part system called **Vito**: a Laravel 12 backend, a Flutter customer app, and a Flutter driver app. All share the same API.

```
drivemond-admin-new-install-3.1/   # Laravel 12 backend
drivemond-user-app-3.1/            # Flutter customer app
drivemond-driver-app-3.1/          # Flutter driver app
landing/                            # Vanilla HTML QR token validation page
```

### Production Readiness
System is ~75% production-ready. Key fixes implemented:
- PIN-based auth (username + 6-digit PIN)
- Real-time order updates via Pusher
- Automatic refunds on ride cancellation
- Location permission enforcement for drivers
- Booking confirmation before submission
- Mart order delivery proof upload
- Camera permission handling for QR scanner

### Languages Supported
English (en) and Spanish (es) only. Arabic (ar) has been removed.

---

## Backend (Laravel 12)

**Working directory:** `drivemond-admin-new-install-3.1/`

### Commands

```bash
composer install --no-interaction --no-scripts --ignore-platform-reqs
cp .env.example .env && php artisan key:generate
php artisan passport:keys --force
php artisan migrate
php artisan serve

# Run the end-to-end Vito flow test (SQLite in-memory, no DB setup needed)
php artisan test --filter=VitoFlowTest

# Run the mart unit tests (order totals, promo codes, product/stock rules)
php artisan test tests/Unit

# Run static analysis (only on Vito controllers)
./vendor/bin/phpstan analyse --level=0 \
  Modules/AuthManagement/Http/Controllers/Api/VitoAuthController.php \
  Modules/AuthManagement/Http/Controllers/Api/QrTokenController.php \
  Modules/TripManagement/Http/Controllers/Api/Customer/VitoMartController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoTripController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoParcelController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoMartDriverController.php \
  Modules/TripManagement/Http/Controllers/Api/Admin/VitoMartAdminApiController.php \
  Modules/Gateways/Http/Controllers/Api/VitoStripeController.php
```

> PHPStan only covers the **API** controllers above. The mart admin **Web** controllers
> (`Http/Controllers/Web/Mart*AdminController.php`) are intentionally excluded — they use the
> `Toastr::` facade, which PHPStan level 0 flags as "static call to instance method" false positives.
> Verify Blade/admin changes with `php artisan view:cache` (compiles every view) instead.

### Module Architecture

Uses [`nwidart/laravel-modules`](https://nwidart.com/laravel-modules). Each module under `Modules/` is self-contained:

```
Modules/{Module}/
  Entities/          # Eloquent models
  Http/Controllers/
    Api/             # JSON API controllers (Customer/, Driver/, Admin/)
    Web/             # Admin panel controllers
  Routes/
    api.php          # Module API routes (auto-loaded)
    web.php          # Module web routes
  Service/           # Business logic layer
  Repository/        # Data access layer
  Database/
    Migrations/      # Module migrations
  Transformers/      # API Resources
  Providers/         # Module service providers
```

**Key modules:**
- `AuthManagement` — QR token gate, PIN-based auth, username registration
- `TripManagement` — Rides (VitoRide), parcels (VitoSend), mart orders (VitoMart). Mart entities:
  `MartProduct`, `MartCategory`, `MartOrder`, `MartOrderItem`, `MartPromoCode`, `MartReview`.
- `ChattingManagement` — Real-time messaging (polymorphic: TripRequest or MartOrder)
- `Gateways` — Stripe PaymentIntent with idempotent webhooks
- `UserManagement` — Profiles, driver details
- `ZoneManagement`, `VehicleManagement`, `PromotionManagement`, etc.

### Authentication Flow

Two parallel auth paths co-exist:

1. **PIN path (primary Vito flow):** username + 6-digit PIN. New users must first validate a QR/invite token (`POST /api/auth/qr-token/validate`). Routes in `Routes/api.php` under `VitoAuthController`. Tokens: 1-hour expiry for customers, 7-day for drivers. PIN change revokes all other active sessions.
2. **OTP path (alternative):** phone number + SMS OTP via `ClientOtpAuthController` (`send-otp` → `otp-verification` → `registration-from-otp`). OTPs are bcrypt-hashed in `vito_otps`, 5-min expiry, 30s resend cooldown, 5-attempt lock. Not gate-guarded (no QR required).

Legacy routes (phone/password, social-login, Firebase-OTP) also remain active — do not remove them.

- API auth: Laravel Passport with scopes `AccessToCustomer`, `AccessToDriver`, `AccessToSuperAdmin`. Always add the correct `scope:` middleware to new routes.
- PIN lockout: 5 failed attempts → temporary block (default 15 min). Lock is row-level (`lockForUpdate` in a transaction).
- QR token generation/revocation: `throttle:10,1` rate limited, requires `scope:AccessToSuperAdmin`.

### Default Dev Credentials

`php artisan migrate --seed` (or `db:seed`) creates these accounts via `DefaultUsersSeeder` (idempotent — safe to re-run):

| Role | Login | Secret |
|------|-------|--------|
| Admin | `admin@admin.com` (web panel) | `12345678` |
| Customer | username `customer` (user app) | PIN `123456` |
| Driver | username `driver` (driver app) | PIN `123456` (pre-approved) |

### Test File

`tests/Feature/VitoFlowTest.php` — creates all needed tables in-memory per test run (no external DB). Covers QR tokens, user registration, login, rides, parcels, mart orders (with promo codes), driver acceptance, delivery proof, Stripe payment, and wallet. **Always update tearDown's `dropIfExists` list when adding new tables to the test.**

`tests/Unit/` — pure unit tests that don't touch the DB: `MartOrderTotalCalculationTest` (server-side total math), `MartPromoCodeTest`, `MartProductTest`, `MartOrderTest`. Add unit coverage here for new server-side calculation/business rules.

### Key Patterns

- **Server-side order totals:** The client never sends a price. Backend computes total = (Σ product.price × qty) − promo_discount + tip + delivery_fee. The delivery fee comes from the `mart_delivery_fee` cached config (default 0, no tax applied) and is exposed to the app via `ConfigController`; the persisted order stores it in the `delivery_fee` column.
- **Atomic DB operations:** Use `DB::transaction()` with `lockForUpdate()` for promo code `used_count`, stock decrement, token redemption, etc.
- **Idempotency middleware:** `idempotent` (`app/Http/Middleware/IdempotencyKey.php`) deduplicates POST/PUT/PATCH by request signature. Applied to order creation routes. Stripe webhook uses `stripe_event_id` UNIQUE constraint for its own dedup.
- **Polymorphic chat channels:** `channel_lists.channelable_type` is either `TripRequest::class` or `MartOrder::class`. Check `channelable_type` in `ChattingController` before branching.
- **Real-time broadcast:** Backend uses Laravel Reverb (`BROADCAST_DRIVER=reverb`). `CustomerRideChatEvent`/`DriverRideChatEvent` for rides; `CustomerMartOrderChatEvent`/`DriverMartOrderChatEvent` for mart. Always wrap `::broadcast()` calls in `try/catch` and check `checkReverbConnection()` first.
- **Push notifications:** Use `sendDeviceNotification()` helper, wrapped in try/catch so failures never break the main flow.
- **Health endpoint:** `GET /api/health` — unauthenticated, throttled, checks DB + cache. Use for local smoke-testing or load balancer probes.
- **Queue worker required:** Ride-timeout auto-cancel job needs `QUEUE_CONNECTION=redis` and a running `php artisan queue:work` (or Supervisor). File-queue mode silently drops these jobs.

### VitoMart Admin Section

VitoMart has a dedicated, permission-gated admin panel section (sidebar `nav-category` "VitoMart"),
not just product CRUD. Pieces live under `Modules/TripManagement/`:

- **Web controllers** (`Http/Controllers/Web/`): `VitoMartAdminController` (products),
  `MartOrderAdminController` (orders list/details/status/export), `MartPromoCodeAdminController`,
  `MartReviewAdminController` (read-only), `MartCategoryAdminController`, `MartDashboardController`.
  Routes are the `admin/mart/*` group in `Routes/web.php` (names `admin.mart.*`; product names kept
  as `admin.mart.products.*` for back-compat). Views under `Resources/views/admin/mart/`.
- **Permissions:** a `vito_mart` module entry in `app/Lib/Constant.php` `MODULES` (drives the
  role-permission UI) + gates in `app/Providers/AuthServiceProvider.php`
  (`vito_mart_view/add/edit/delete/log/export` and `vito_mart_status`). Every web controller method
  calls `$this->authorize('vito_mart_*')`.
- **Sidebar order-count badges:** injected once via a view composer in
  `app/Providers/GlobalDataServiceProvider.php` (`$martOrderCounts`, single grouped query).
- **Categories** are a lookup table (`mart_categories`); products keep `category` as a string (no FK).
  Active categories feed the product dropdown and `GET /api/customer/mart/categories`.
- **Audit:** status changes and product CRUD write to `vito_audit_log` via the shared
  `Http/Controllers/Concerns/LogsVitoAudit` trait.

**Order status state machine:** the single source of truth is `MartOrder::STATUS_TRANSITIONS`
(target → allowed-from statuses), consumed by **both** `VitoMartDriverController::updateStatus` (API)
and `MartOrderAdminController::updateStatus` (admin). `pending → accepted → picked_up → delivered`;
`cancelled` from `pending` or `accepted`. Change transitions here, not in the controllers.

### Adding a New API Endpoint

1. Add route in the module's `Routes/api.php` (legacy base routes) **or** `Routes/vito_api.php` (Vito-specific routes — use this for new Vito features). Both are auto-loaded. Place under `auth:api` + `maintenance_mode` + `scope:Access*` middleware.
2. Add controller method in `Modules/{Module}/Http/Controllers/Api/{Role}/`.
3. If it's a new mart order status transition, update `MartOrder::STATUS_TRANSITIONS` (shared by API + admin).
4. Add a test case to `VitoFlowTest.php` (and update `tearDown` `dropIfExists` if you add a table).

---

## Flutter Apps

Both apps share the same structure. Commands below work from either app root.

**User app:** `drivemond-user-app-3.1/HexaRide-User-app-release-3.1/`  
**Driver app:** `drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/`

### Commands

```bash
flutter pub get
flutter analyze --no-fatal-infos
flutter test test/vito_flows_test.dart

# Build (API keys required at build time)
flutter build apk --debug \
  --dart-define=MAPS_API_KEY=<key> \
  --dart-define=STRIPE_PUBLISHABLE_KEY=<key>
```

### Architecture

**State management:** GetX (`GetxController` + `GetBuilder`). No Provider, no Riverpod.

**DI wiring:** All dependencies registered in `lib/helper/di_container.dart` using `Get.lazyPut()`. The chain is:

```
ApiClient → Repository → Service → Controller
```

When adding a new feature, register all four layers in `di_container.dart`.

**Feature structure:**

```
lib/features/{feature}/
  controllers/         # GetxController (state + actions)
  domain/
    models/            # Plain Dart models (fromJson)
    repositories/      # Interface + implementation (calls ApiClient)
    services/          # Interface + implementation (delegates to repo)
  screens/             # StatefulWidget / StatelessWidget pages
  widgets/             # Feature-local reusable widgets
```

**Navigation:** Always `Get.to(() => Screen())` or `Get.off(() => Screen())`. No named routes.

**API calls:** Use `Get.find<ApiClient>()` methods:
- `getData(url)` — GET
- `postData(url, body)` — POST/PUT with JSON
- `putData(url, body)` — PUT
- `postMultipartData(url, body, files, otherFile)` — multipart upload

### Localization

Translation keys live in `assets/language/en.json` and `es.json` (Arabic `ar.json` has been removed — English and Spanish are the only supported languages). Always add a new UI string to **both** language files. The test `vito_flows_test.dart` enforces exact key parity between EN and ES (same key count, no keys missing from or extra in either file), so an unmatched key fails CI.

Use `.tr` extension on string literals: `'some_key'.tr`.

### Real-time (Pusher)

Both apps use `dart_pusher_channels`. Channel naming convention:

| Chat type | Customer channel | Driver channel |
|-----------|-----------------|----------------|
| Ride      | `private-customer-ride-chat.{tripId}` | `private-driver-ride-chat.{tripId}` |
| Mart order | `private-customer-mart-chat.{orderId}` | `private-driver-mart-chat.{orderId}` |

Subscribe in the controller method, bind the event, and check `id == eventData['channel_conversation']['channel']['trip_id']` (for rides) or `order_id` (for mart) before inserting into the message list.

### Mart Feature (both apps)

The mart feature follows the standard GetX layering: `lib/features/mart/{controllers,domain/{models,
repositories,services}}` with a `MartController` registered (all four layers) in `di_container.dart`.
Customer endpoints (products, categories, orders, cancel, review) live in the user app's
`MartController`; driver endpoints (pending/my-orders, accept, status) in the driver app's. Older
mart screens (`mart_store_screen`, `mart_order_tracking_screen`, `mart_delivery_screen`) still carry
significant inline state — migrate them onto the controller incrementally (method-by-method behind
`GetBuilder`), keeping each step `flutter analyze`-clean. New screens (`mart_order_history_screen`,
`mart_product_details_screen`) already consume the controller.

### Mart Order Chat (User App)

- `MessageController.createMartChannel(driverId, orderId, driverName)` → navigates to `MartMessageScreen`
- `MessageController.subscribeMartMessageChannel(orderId)` — call from `MartMessageScreen.initState`
- `MessageController.sendMartMessage(channelId, orderId)` — sends `order_id` field (not `trip_id`)
- Chat button in `MartOrderTrackingScreen` is disabled until `_driverId` is non-empty (driver not yet assigned)

### Mart Order Chat (Driver App)

- `ChatController.createMartChannel(customerId, orderId, customerName)` → navigates to `MartDriverMessageScreen`
- Same subscribe/send pattern with mart-specific methods
- Chat button lives in `MartDeliveryScreen._buildCustomerInfo()` alongside the call button

---

## CI/CD

`.github/workflows/vito-ci.yml` — runs on every push/PR to `master` and `vlad`:
1. Laravel: PHPStan level 0 on Vito controllers + `VitoFlowTest` (with clover coverage)
2. Flutter User App: analyze + unit tests + debug APK build (artifact retained 14 days)
3. Flutter Driver App: same

`.github/workflows/build-apk.yml` — triggered on `v*` tags or manual dispatch; builds release APKs.

Required GitHub secrets: `MAPS_API_KEY`, `STRIPE_PUBLISHABLE_KEY`.

---

## Mapbox Integration

### Android Setup
Mapbox token is configured via Gradle build config. Add to `android/app/build.gradle`:
```groovy
android {
    defaultConfig {
        manifestPlaceholders += [MAPBOX_ACCESS_TOKEN: project.findProperty("MAPBOX_ACCESS_TOKEN") ?: ""]
    }
}
```
The token is injected via `--dart-define=MAPBOX_ACCESS_TOKEN=...` during build.

### iOS Setup
1. Add Mapbox access token to `ios/Runner/Info.plist`:
```xml
<key>MGLMapboxAccessToken</key>
<string>YOUR_MAPBOX_ACCESS_TOKEN</string>
```

2. Required iOS permissions in `Info.plist`:
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Vito needs your location to show nearby drivers and destinations</string>
<key>NSLocationAlwaysUsageDescription</key>
<string>Vito needs your location to track trips in the background</string>
```

3. Add to `Podfile` (iOS 12+):
```ruby
platform :ios, '12.0'
```

### VitoMap Widget
The `lib/common_widgets/vito_map.dart` widget provides a provider-agnostic map interface:
- **Google Maps** (default): Standard `google_maps_flutter` widget
- **Mapbox**: Uses `mapbox_maps_flutter` when `config.mapProvider == 'mapbox'`

Features:
- Loading indicator during map initialization
- Error state display when token is missing/invalid
- `onCameraMove` callback for both providers
- My-location button for Mapbox
- Proper annotation manager cleanup on dispose
- Custom style support via `mapboxStyleUri` parameter

## Conventions

- **UUIDs everywhere:** All primary keys use `HasUuids` trait (Laravel) / UUID strings (Flutter models).
- **Soft deletes:** Most entities use `SoftDeletes`. Use `->withTrashed()` when needed.
- **Mart order statuses:** `pending` → `accepted` → `picked_up` → `delivered` (or `cancelled` from `pending`/`accepted`). Canonical map: `MartOrder::STATUS_TRANSITIONS`.
- **No client-sent totals:** Strip `total` / `amount` from any order creation request body; always recompute server-side.
- **Structured logging:** Backend logs JSON to `stderr` (`json_stderr` channel). Every request carries an `X-Request-Id` header (added by `RequestId` middleware), propagated through logs.
- **Security headers:** `SecurityHeaders` middleware adds `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` on every response — do not duplicate them manually.
