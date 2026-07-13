# Production Credentials — everything to supply (Backend · Android · iOS)

One consolidated checklist of what you hand over so the whole system runs on **your** accounts.
Status legend: ✅ already set · ⚠️ vendor/placeholder (must replace) · ❌ missing · ⭕ optional.

Bundle IDs / package names: `com.sixamtech.hexarideuser` (user), `com.sixamtech.hexariderider` (driver).
API host: `https://dacatlon.store`.

---

## 0. The one thing that ties it together: a single Firebase project
Create **one** Firebase project (console.firebase.google.com) and register **four** apps + one key:
1. Android **user** app → `google-services.json`
2. Android **driver** app → `google-services.json`
3. iOS **user** app → `GoogleService-Info.plist`
4. iOS **driver** app → `GoogleService-Info.plist`
5. **Service account** (Project settings → Service accounts → *Generate new private key*) → one JSON for
   the backend to send push (FCM v1).

Today all four app configs point at the **vendor** project `drivevalley-fdb7f` (⚠️). Replacing them is
what makes push notifications land on your project. **Hand me these 4 files + the 1 service-account JSON.**

---

## 1. BACKEND (Laravel, on `dacatlon.store`)
The server already has a working `.env` from deployment. Remaining items:

| # | Supply | Status | Where to get it | Where it goes |
|---|--------|--------|-----------------|---------------|
| B1 | Firebase **service-account JSON** | ❌ | Firebase → Project settings → Service accounts → Generate key | backend (push config) — **required for push** |
| B2 | **Stripe** secret key + webhook secret | ✅ set | dashboard.stripe.com → Developers → API keys / Webhooks | `.env` `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` (+ admin panel) |
| B3 | **Google Maps server key** (server-side, IP-restricted) | ✅ set (admin) | Google Cloud → Credentials (enable Geocoding, Distance Matrix, Places, Directions) | Admin → 3rd-Party → Google Map |
| B4 | **SMTP mail** (host/user/pass/from) | ⚠️ verify | your mail provider (SES, Mailgun, Gmail app-pw…) | `.env` `MAIL_*` |
| B5 | **SMS gateway** (Twilio SID/token/from) | ⭕ | twilio.com console | Admin → 3rd-Party → SMS (or `.env` `TWILIO_*`) — only if you use OTP SMS |
| B6 | **Mapbox** public token | ✅ optional | account.mapbox.com | Admin → 3rd-Party → Map (only if switching map provider to Mapbox) |

> Reverb/Pusher, DB, APP_KEY, admin seed — all ✅ already in the deployed `.env`.

---

## 2. ANDROID (both apps)
| # | Supply | Status | Where to get it | Where it goes |
|---|--------|--------|-----------------|---------------|
| A1 | 2× **google-services.json** (user + driver, your Firebase) | ⚠️ vendor | Firebase → the two Android apps (§0) | `…/android/app/google-services.json` (each app) |
| A2 | **Upload keystore** (`.jks`) + alias + passwords | ❌ | generate once: `keytool -genkey -v -keystore upload.jks -alias upload -keyalg RSA -keysize 2048 -validity 10000` | `…/android/key.properties` + the `.jks` (**not committed**) |
| A3 | **Google Maps Android key** (restrict to package + release SHA-1) | ❌ **billing** | Google Cloud → new project → **enable Billing** → enable *Maps SDK for Android* → key | `build.gradle.kts` / CI secret `MAPS_API_KEY` |
| A4 | **Stripe publishable key** | ✅ via build | dashboard.stripe.com → API keys | `--dart-define`/CI secret `STRIPE_PUBLISHABLE_KEY` |
| A5 | **Play Console** account ($25 one-time) | ❌ (to publish) | play.google.com/console | upload the AAB/APK |

> **A3 grey-map root cause (confirmed 2026-07-13):** the committed fallback key's Cloud project has
> **no billing account** — Google rejects every Maps request ("You must enable Billing…"), which
> renders solid grey tiles in both apps. Fix: create your own Cloud project, **enable billing**
> ($200/mo free credit), enable *Maps SDK for Android* + *Maps SDK for iOS*, create a key, restrict
> it (Android: both package names + release SHA-1; iOS: both bundle IDs), then hand it over — it
> goes into both `build.gradle.kts` fallbacks, both `ios/Runner/AppDelegate.swift`, and the
> `MAPS_API_KEY` repo secret. No code change can work around missing billing.

> Without **A2** the release APK is **debug-signed** and Play Store will reject it. The keystore must be
> generated once and kept safe — losing it means you can't update the app.

---

## 3. iOS (both apps) — full detail in `IOS_PRODUCTION_CREDENTIALS.md`
| # | Supply | Status | Where to get it | Where it goes |
|---|--------|--------|-----------------|---------------|
| I1 | **Apple Developer Program** ($99/yr) | ❌ | developer.apple.com/programs | gates everything |
| I2 | **Team ID** | ❌ | developer.apple.com → Membership | Xcode Signing |
| I3 | 2× **App IDs** with Push + Associated Domains | ❌ | developer.apple.com → Identifiers | — |
| I4 | **APNs Auth Key `.p8`** (+ Key ID) | ❌ | developer.apple.com → Keys | upload to Firebase → Cloud Messaging |
| I5 | 2× **GoogleService-Info.plist** (your Firebase) | ⚠️ placeholder | Firebase → the two iOS apps (§0) | each app's `ios/Runner/` |
| I6 | **Google Maps iOS key** (restrict to bundle IDs) | ⚠️ shared key | Google Cloud → iOS key (enable *Maps SDK for iOS*) | `ios/Runner/AppDelegate.swift` |
| I7 | **Mapbox downloads token** `sk.…` | ❌ | account.mapbox.com (scope DOWNLOADS:READ) | `~/.netrc` on the build Mac |
| I8 | **App Store Connect** app records | ❌ | appstoreconnect.apple.com | — |
| I9 | Distribution cert + profiles | auto | Xcode Automatic signing | Xcode |
| I10 | AASA file for universal links | ⭕ | host on dacatlon.store | `/.well-known/apple-app-site-association` |

---

## What to hand me right now (fastest path to a real build)
Give me these and I wire them in / document exactly where they go:
1. **Firebase**: 2× `google-services.json` + 2× `GoogleService-Info.plist` + 1× service-account JSON (§0).
2. **Android upload keystore** + `key.properties` values (A2).
3. **Stripe**: publishable + secret + webhook secret (confirm the live ones) (B2/A4).
4. **Apple**: Team ID + APNs `.p8` (Key ID) once you have the Developer account (I2/I4).
5. **Mapbox** downloads token if you'll build iOS (I7).
6. Confirm **SMTP** mail creds (B4) and whether you use **SMS OTP** (B5).

Secrets marked ✅ are already in place from earlier work. Send files/keys and I'll place them,
update the CI secrets list, and verify each end-to-end.
