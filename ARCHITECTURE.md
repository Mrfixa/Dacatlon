# Vito — Architecture & Flow Logic

> End-to-end architecture of the Vito platform: a three-app ride-hailing + parcel-delivery +
> quick-commerce system sharing one Laravel API. This document explains how the pieces fit
> together and how each user journey flows through them. For "where is X" lookups use
> `CODEBASE_MAP.md` (layout) and `API_INDEX.md` (endpoint → controller); this file explains *why*
> and *how*.

---

## 1. System Overview

Vito is one product surface (brand: **Vito**, domain **dacatlon.store**) delivered as four
deployables that all talk to a single backend API:

| Component | Path | Stack | Role |
|-----------|------|-------|------|
| **Backend / Admin** | `drivemond-admin-new-install-3.1/` | Laravel 12, PHP 8.2+, nwidart modules | REST API + Blade admin panel |
| **Customer app** | `drivemond-user-app-3.1/HexaRide-User-app-release-3.1/` | Flutter, GetX | Rider / shopper / sender app |
| **Driver app** | `drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/` | Flutter, GetX | Driver / courier app |
| **Landing** | `landing/` | Vanilla HTML/JS | Public QR-token validation page |

Three business verticals run on the same trip/order spine:

- **VitoRide** — passenger ride-hailing (`TripRequest`, `type = ride_request`)
- **VitoSend** — parcel delivery (`TripRequest`, `type = parcel`)
- **VitoMart** — quick-commerce grocery/store orders (`MartOrder`)

```
                         ┌───────────────────────────┐
   Customer app  ─────►  │                           │  ◄─────  Driver app
   (Flutter/GetX)        │   Laravel 12 API          │          (Flutter/GetX)
                         │   (Passport OAuth scopes) │
   Admin panel  ◄─────►  │   16 nwidart modules      │
   (Blade)               │                           │
                         └───────────┬───────────────┘
                                     │
        ┌──────────────┬─────────────┼───────────────┬──────────────┐
     MySQL          Redis         Reverb           Stripe        FCM / SMS
   (data +        (cache +      (websockets:      (PaymentIntent  (push +
    UUID PKs)     queue +        chat + live       + idempotent    OTP)
                   locks)        tracking)         webhooks)
```

---

## 2. Backend Architecture (Laravel 12)

### 2.1 Module system

Uses [`nwidart/laravel-modules`](https://nwidart.com/laravel-modules). Every feature is a
self-contained module under `Modules/` with its own routes, migrations, entities, services,
repositories, controllers, transformers and providers. The 16 modules:

| Module | Responsibility |
|--------|----------------|
| **AuthManagement** | QR-token gate, PIN auth, OTP auth, legacy auth, sessions |
| **TripManagement** | Rides, parcels **and** VitoMart (mart entities live here) |
| **ParcelManagement** | Parcel-specific info attached to a `TripRequest` |
| **FareManagement** | Trip fare / bidding / distance-price calculation |
| **UserManagement** | Customer & driver profiles, driver details, wallet, levels |
| **ZoneManagement** | Geofenced operating zones (rides matched within a zone) |
| **VehicleManagement** | Vehicle categories, models, driver vehicle records |
| **PromotionManagement** | Coupons, discounts, banners, referral |
| **Gateways** | Payment gateway integrations (Stripe PaymentIntent + webhooks) |
| **TransactionManagement** | Wallet ledger, payouts, driver earnings |
| **ChattingManagement** | Polymorphic real-time chat (trip **or** mart order) |
| **ReviewModule** | Ratings/reviews for rides + mart |
| **BusinessManagement** | Business settings, config API, feature toggles |
| **PromotionManagement / BlogManagement / AiModule / AdminModule** | CMS, blog, AI helpers, admin shell |

### 2.2 Per-request layering

```
Route (Module/Routes/{api,vito_api,web}.php)
  → Middleware (auth:api · scope:Access* · maintenance_mode · idempotent · throttle · SecurityHeaders · RequestId)
    → Controller (Http/Controllers/Api/{Customer|Driver|Admin}/…)
      → Service (business logic)          ← always compute money/status here
        → Repository (Eloquent data access)
          → Entity (Model, HasUuids + SoftDeletes)
      → Transformer (API Resource) → JSON
```

- **Two route files per module are auto-loaded:** `Routes/api.php` (legacy base routes) and
  `Routes/vito_api.php` (new Vito-specific routes — use this for new features).
- **Conventions:** UUID primary keys everywhere (`HasUuids`); soft deletes on most entities;
  server-side money math only (client never sends a price/total).

### 2.3 Cross-cutting infrastructure

| Concern | Mechanism |
|---------|-----------|
| **Request tracing** | `RequestId` middleware stamps `X-Request-Id`, propagated into logs |
| **Structured logging** | JSON to `stderr` via the `json_stderr` channel |
| **Security headers** | `SecurityHeaders` middleware (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) |
| **Idempotency** | `IdempotencyKey` middleware dedupes POST/PUT/PATCH by request signature (order creation); Stripe webhooks dedupe on `stripe_event_id` UNIQUE |
| **Atomicity** | `DB::transaction()` + `lockForUpdate()` for promo `used_count`, stock decrement, token redemption, PIN lockout |
| **Health** | `GET /api/health` — unauthenticated, throttled, checks DB + cache |
| **Async jobs** | Ride-timeout auto-cancel requires `QUEUE_CONNECTION=redis` + a running `queue:work` |

---

## 3. Authentication & Registration

Two parallel auth paths co-exist, plus retained legacy paths. **API auth is Laravel Passport**
with scopes `AccessToCustomer`, `AccessToDriver`, `AccessToSuperAdmin`. Every route carries the
correct `scope:` middleware.

### 3.1 Primary: QR-gated PIN auth (the Vito flow)

New users must first validate an **invite QR token** before they can register.

```
Admin (scope:AccessToSuperAdmin, throttle:10,1)
   POST /api/qr-token/generate  ──►  QrToken (role-scoped: customer OR driver)
                                     • customer token: 1-hour expiry
                                     • driver token:   7-day expiry
        │
        ▼  (token shown as QR / link; landing/ page can validate publicly)
App: TokenGateScreen ─► QrScannerScreen ─► scans token
        │
        ▼
   POST /api/{customer|driver}/auth/pin-register   (VitoAuthController::pinRegister)
        • qr_token REQUIRED (64 chars), validated & redeemed atomically, role-scoped
        • driver route additionally gated by business setting `driver_self_registration`
        • username + 6-digit PIN
        │
        ▼
   POST /api/{customer|driver}/auth/pin-login       (username + PIN)
        • 5 failed attempts → temporary lock (default 15 min), row-level lockForUpdate
        • PIN change revokes all other active sessions
```

**Feature gate — driver self-registration:** the driver app's login screen only shows the
"Sign up → QR" button when `config.self_registration == true`, which mirrors the
`driver_self_registration` business setting (`Driver/ConfigController.php`). When it is off, the
app shows a "contact support" link instead. The QR path is fully wired; this toggle just
shows/hides its entry point. Set it in **Admin → Business Settings → Driver Settings**.

### 3.2 Alternative: SMS-OTP auth (not QR-gated)

`ClientOtpAuthController`: `send-otp → otp-verification → registration-from-otp`. OTPs are
bcrypt-hashed in `vito_otps`, 5-min expiry, 30s resend cooldown, 5-attempt lock.

### 3.3 Legacy (retained, do not remove)

Phone/password, social-login, Firebase-OTP routes remain active for backward compatibility.

### 3.4 PIN recovery

`forgot-pin/send-otp → forgot-pin/reset` (SMS OTP, throttle:5,1 because each hit sends an SMS).

---

## 4. The Three Verticals — Flow Logic

### 4.1 VitoRide (ride-hailing)

`TripRequest.type = ride_request`. Status lifecycle (`app/Lib/Constant.php`):

```
pending ──► accepted ──► ongoing ──► completed
   │            │            │
   └── cancelled┴── cancelled┘   (+ automatic refund on eligible cancellation)
```

Flow:
1. **Customer** sets pickup + destination → backend computes fare (FareManagement, distance ×
   zone/vehicle pricing) → **booking confirmation** before submission.
2. Trip enters `pending`; broadcast to eligible drivers **within the zone** (ZoneManagement).
3. **Driver** accepts → `accepted`; a **queue timeout job** auto-cancels if no driver accepts
   (needs Redis queue worker).
4. Driver arrives → starts trip → `ongoing`; live location tracked via Reverb.
5. Trip ends → `completed`; fare captured (cash / wallet / Stripe); ratings exchanged.
6. Cancellation triggers **automatic refund** where applicable.

### 4.2 VitoSend (parcel)

`TripRequest.type = parcel`, with `ParcelInformation` + `ParcelUserInfomation` attached. Adds
pickup/return semantics:

```
pending ──► accepted ──► out_for_pickup ──► ongoing ──► completed
                              │                │
                              └── returning ───┘   (undeliverable → return leg)
                     cancelled from pending/accepted (+ parcel refund)
```

### 4.3 VitoMart (quick-commerce)

Separate spine: `MartOrder` + `MartOrderItem`, catalog `MartProduct` / `MartCategory`, plus
`MartPromoCode` and `MartReview`. **Single source of truth for status is
`MartOrder::STATUS_TRANSITIONS`** (target → allowed-from), consumed by *both* the driver API
(`VitoMartDriverController::updateStatus`) and admin (`MartOrderAdminController::updateStatus`):

```php
'accepted'  => ['pending'],
'picked_up' => ['accepted'],
'delivered' => ['picked_up'],
'cancelled' => ['pending', 'accepted'],
```

```
pending ──► accepted ──► picked_up ──► delivered
   │            │
   └── cancelled┘
```

**Server-side order total (client never sends a price):**

```
total = Σ(product.price × qty) − promo_discount + tip + delivery_fee
```

- `delivery_fee` comes from the cached `mart_delivery_fee` config (default 0, no tax), exposed to
  the app via `ConfigController`, and persisted on the order's `delivery_fee` column.
- Promo `used_count` and stock decrement run inside `DB::transaction()` with `lockForUpdate()`.
- Order-creation routes carry the `idempotent` middleware.
- On delivery the driver uploads **delivery proof**.

Flow: browse products/categories → cart → place order (idempotent, totals recomputed) → driver
sees pending orders → accept → pick up at store → deliver (proof upload) → review.

---

## 5. Real-time (Reverb / Pusher)

Backend broadcasts with **Laravel Reverb** (`BROADCAST_DRIVER=reverb`); both apps subscribe with
`dart_pusher_channels`. Every `::broadcast()` is wrapped in try/catch and preceded by
`checkReverbConnection()`, so a websocket outage never breaks the HTTP flow.

**Chat is polymorphic:** `channel_lists.channelable_type` is `TripRequest::class` **or**
`MartOrder::class`; `ChattingController` branches on it.

| Purpose | Customer channel | Driver channel |
|---------|------------------|----------------|
| Ride chat | `private-customer-ride-chat.{tripId}` | `private-driver-ride-chat.{tripId}` |
| Mart chat | `private-customer-mart-chat.{orderId}` | `private-driver-mart-chat.{orderId}` |
| Live tracking | driver location events during `ongoing` | — |

Events: `CustomerRideChatEvent` / `DriverRideChatEvent`, `CustomerMartOrderChatEvent` /
`DriverMartOrderChatEvent`. Clients filter by `trip_id` (rides) or `order_id` (mart) before
inserting a message.

**Push notifications:** `sendDeviceNotification()` helper (FCM), always try/catch-wrapped.

---

## 6. Payments

| Method | Flow |
|--------|------|
| **Stripe** | `PaymentIntent` created server-side; confirmation via publishable key in app; **idempotent webhooks** dedupe on `stripe_event_id` UNIQUE |
| **Wallet** | `TransactionManagement` ledger; driver earnings + customer wallet; atomic debits/credits |
| **Cash** | Recorded at trip/order completion |

Payment status constants: `paid` / `unpaid` / `partial_paid`. Automatic refunds on eligible
ride/parcel cancellations. **No client-sent totals** — the backend always recomputes.

---

## 7. Flutter App Architecture (both apps)

Identical structure in the customer and driver apps.

- **State:** GetX (`GetxController` + `GetBuilder`). No Provider/Bloc/Riverpod.
- **DI:** everything registered in `lib/helper/di_container.dart` via `Get.lazyPut()`, chained
  `ApiClient → Repository → Service → Controller`. New feature = register all four layers.
- **Feature layout:**
  ```
  lib/features/{feature}/
    controllers/        # GetxController (state + actions)
    domain/
      models/           # plain Dart models (fromJson)
      repositories/     # interface + impl (calls ApiClient)
      services/         # interface + impl (delegates to repo)
    screens/            # pages
    widgets/            # feature-local widgets
  ```
- **Navigation:** always `Get.to(() => Screen())` / `Get.off(...)`. No named routes.
- **API client** (`ApiClient`): `getData`, `postData`, `putData`, `postMultipartData`; exponential
  backoff on transient failures.
- **Localization:** `assets/language/{en,es}.json` (English + Spanish only; Arabic removed).
  Strings use `'key'.tr`. A test enforces **exact EN/ES key parity** and that every `'key'.tr`
  referenced in `lib/` exists — an unmatched key fails CI.
- **Config-driven UI:** `SplashController.config` (from the backend config endpoint) toggles
  features like `selfRegistration`, map provider, delivery fee, business contact.
- **Maps:** `lib/common_widgets/vito_map.dart` is provider-agnostic — Google Maps by default,
  Mapbox when `config.mapProvider == 'mapbox'` (loading/error states, camera callbacks, cleanup).

**Real-time in the app:** subscribe in the controller method, bind the event, filter on
`trip_id` / `order_id`, insert into the message/tracking list under `GetBuilder`.

---

## 8. Admin Panel (Blade)

Server-rendered admin under `Modules/AdminModule` + per-module `Http/Controllers/Web`. Highlights:

- **VitoMart admin section** (permission-gated, sidebar `nav-category` "VitoMart"):
  product CRUD, orders list/detail/status/export, promo codes, read-only reviews, categories,
  dashboard. Views under `Modules/TripManagement/Resources/views/admin/mart/`.
- **Permissions:** `vito_mart` entry in `app/Lib/Constant.php` `MODULES` + gates in
  `AuthServiceProvider`; every web method calls `$this->authorize('vito_mart_*')`.
- **Audit trail:** status changes + product CRUD write to `vito_audit_log` via the shared
  `LogsVitoAudit` trait.
- **Branding:** logo/favicon are uploaded in **Business Settings** (`header_logo`, `favicon`,
  stored in `storage/app/public/business/`) and resolve with a fallback to the static
  `public/assets/admin-module/img/` files — so branding is fully panel-configurable.

---

## 9. Data Model (key entities)

```
User (customer|driver)
 ├─ DriverDetail, Vehicle
 ├─ Wallet ── Transaction (ledger)
 └─ QrToken (role-scoped invite)

TripRequest (type: ride_request | parcel)
 ├─ FareBiddings / fare fields
 ├─ ParcelInformation + ParcelUserInfomation (parcel only)
 ├─ ParcelRefund / ride refund
 ├─ Review
 └─ ChannelList (polymorphic chat) ── Conversation

MartOrder ── MartOrderItem ── MartProduct ── MartCategory
 ├─ MartPromoCode (used_count, locked on redeem)
 ├─ MartReview
 └─ ChannelList (polymorphic chat)

BusinessSetting (feature toggles, fees, branding, keys)
Zone (geofence)  ·  VehicleCategory  ·  Coupon/Promotion
```

Conventions: `HasUuids` PKs, `SoftDeletes` (`withTrashed()` when needed).

---

## 10. CI/CD & Release Pipeline

Workflows in `.github/workflows/`:

| Workflow | Trigger | Does |
|----------|---------|------|
| **vito-ci.yml** | push/PR to `master`, `vlad`, `main` | PHPStan (Vito API surface) + `VitoFlowTest` (coverage) · both apps: analyze + `vito_flows_test` · debug APK builds |
| **build-apk.yml** | `v*` tags / `main` / manual dispatch | Laravel checks · Admin ZIP · User APK · Driver APK (obfuscated + split-debug symbols) · **create GitHub Release** with all assets |
| **build-ios.yml / release-ios.yml** | tags / dispatch | iOS compile + signed release (needs Apple secrets) |
| **ui-goldens.yml** | push/PR | Flutter golden tests |

- **Test suites:** `tests/Feature/VitoFlowTest.php` builds all tables in-memory (SQLite) and
  drives the whole journey (QR → auth → ride → parcel → mart + promo → driver accept → proof →
  Stripe → wallet). `tests/Unit/` holds pure money/business-rule tests. Flutter
  `test/vito_flows_test.dart` enforces i18n parity + referenced-key existence.
- **Static analysis:** `phpstan.neon` covers Vito API controllers; mart admin Web controllers are
  intentionally excluded (Toastr facade false positives — verify with `php artisan view:cache`).
- **Release versioning:** the release tag is derived from the user-app `pubspec.yaml` `version:`
  (e.g. `3.4.2+20 → v3.4.2`). Bump both pubspecs to cut a new release.
- **Required secrets:** `MAPS_API_KEY`, `STRIPE_PUBLISHABLE_KEY`, `MAPBOX_ACCESS_TOKEN`; iOS
  needs the Apple signing set. Unset → APKs still build but maps/payments are inert at runtime.

---

## 11. Deployment Topology

```
             ┌─────────── Load balancer / TLS (dacatlon.store) ───────────┐
             │                                                             │
        PHP-FPM (Laravel)                Reverb server            Queue worker(s)
        php artisan serve /             php artisan reverb:start  php artisan queue:work
        nginx+fpm                       (websockets)              (redis; ride-timeout jobs)
             │                                                             │
        ┌────┴─────┐                                                       │
      MySQL      Redis  ◄──── cache + sessions + queue + locks ────────────┘
```

Runtime requirements:
- `QUEUE_CONNECTION=redis` **and** a running worker (Supervisor/systemd) — file-queue mode
  silently drops the ride-timeout auto-cancel job.
- `php artisan storage:link` so uploaded logos / proofs / avatars serve from `storage/`.
- `php artisan config:cache` in production → **never call `env()` outside `config/`** (returns
  null when cached; read a config key instead).
- Passport keys (`passport:keys`), tokens expire (30d access / 60d refresh, configurable).
- Production seeding refuses demo defaults unless `SEED_DEMO_USERS=true`; super-admin needs
  `ADMIN_SEED_EMAIL` + `ADMIN_SEED_PASSWORD` (≥12 chars).

---

## 12. Configuration & Feature Toggles

Driven by `BusinessSetting` rows, surfaced to apps via the config endpoints. Notable toggles:

| Setting | Effect |
|---------|--------|
| `driver_self_registration` | Shows/hides the driver Sign-Up → QR entry point |
| `mart_delivery_fee` | Flat mart delivery fee added to server-computed totals |
| map provider | Google Maps vs Mapbox in `vito_map.dart` |
| branding (`header_logo`, `favicon`) | Panel/login/tab logos (fallback to static assets) |
| business contact | "contact support" links, invoices, emails |

---

## 13. Where to Go Next

| I want to… | Read |
|------------|------|
| Find a file/feature | `CODEBASE_MAP.md` |
| Find an endpoint → controller | `API_INDEX.md` |
| Understand a customer screen flow | `CLIENT_APP_FLOWS.md` |
| See known gaps/bugs before "fixing" | `USER_APP_AUDIT.md`, `DRIVER_APP_AUDIT.md`, `VITO_AUDIT.md` |
| Deploy / provision | `DEPLOY.md`, `PRODUCTION_DEPLOYMENT.md` |
| Run the on-device E2E pass | `DEVICE_E2E_RUNBOOK.md` |
| Build iOS | `IOS_BUILD.md` |
| Working rules for this repo | `CLAUDE.md` |
