# iOS Production Credentials — what to supply and where to get each

This is the exact checklist to take the two iOS apps (Vito User / Vito Driver) from the committed
project to a signed App Store release. Build steps are in `IOS_SETUP.md`; this file is only about the
credentials/accounts. Everything is done **once per app** where noted (two bundle IDs):

| App | Bundle ID |
|-----|-----------|
| Vito User | `com.sixamtech.hexarideuser` |
| Vito Driver | `com.sixamtech.hexariderider` |

Legend: 🔑 = a secret/file you supply · 📍 = where it goes in this repo/project.

---

## 1. Apple Developer Program membership — **required, gates everything**
- **Get it:** <https://developer.apple.com/programs/enroll/> — $99/year (individual or organization).
  Organization enrollment needs a D-U-N-S number (free, ~1–2 days). Allow a few days for approval.
- Without this you cannot sign for a real device or submit to the App Store.

## 2. Apple Team ID
- **Get it:** <https://developer.apple.com/account> → **Membership details** → copy the 10-character
  **Team ID** (e.g. `AB12CD34EF`).
- 📍 Set it in Xcode: open `ios/Runner.xcworkspace` → **Runner** target → **Signing & Capabilities** →
  **Team** dropdown. (The project ships `DEVELOPMENT_TEAM` blank on purpose; Automatic signing fills
  it once you pick the team.)

## 3. Two App IDs (Identifiers) with the right capabilities
- **Get it:** <https://developer.apple.com/account/resources/identifiers/list> → **+** → **App IDs** →
  **App** → register **both** bundle IDs above. Enable these capabilities on each:
  - **Push Notifications** (for FCM/APNs).
  - **Associated Domains** (for `applinks:dacatlon.store` universal links — already in
    `ios/Runner/Runner.entitlements`).
  - Leave the rest default. (Do **not** enable Sign in with Apple — the apps don't use it.)
- With Xcode **Automatic** signing you can often skip manual registration — Xcode creates the App IDs
  the first time you build — but enabling Push + Associated Domains here avoids signing errors.

## 4. APNs Auth Key (`.p8`) — **required for iOS push notifications**
- **Get it:** <https://developer.apple.com/account/resources/authkeys/list> → **+** → check
  **Apple Push Notifications service (APNs)** → **Continue** → download the **`AuthKey_XXXXXXXXXX.p8`**
  (⚠️ downloadable **once** — store it safely). Note the **Key ID** and your **Team ID**.
- 📍 Upload it to Firebase (not into the repo): Firebase console → your project →
  **Project settings → Cloud Messaging → Apple app configuration → APNs Authentication Key → Upload**.
  Do this for the Firebase project that backs both apps. This is what lets `firebase_messaging`
  deliver notifications on iOS.

## 5. iOS Firebase apps + `GoogleService-Info.plist` (one per app) — 🔑📍
- **Get it:** <https://console.firebase.google.com> → the **same** project you use for the Android
  `google-services.json` → **Add app → iOS** → enter the bundle ID → download **`GoogleService-Info.plist`**.
  Repeat for the second bundle ID.
- 📍 Replace the placeholder files (drag into `Runner/` in Xcode, keep the filename):
  - `drivemond-user-app-3.1/HexaRide-User-app-release-3.1/ios/Runner/GoogleService-Info.plist`
  - `drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/ios/Runner/GoogleService-Info.plist`
- Until replaced, push/analytics attach to the placeholder project, not yours.

## 6. Google Maps API key for iOS — 🔑📍
- **Get it:** <https://console.cloud.google.com/apis/credentials> → **Create credentials → API key**.
  Enable **Maps SDK for iOS** (APIs & Services → Library). Restrict the key: **iOS apps** → add both
  bundle IDs. (Use a **separate** key from the Android one, each restricted to its platform.)
- 📍 Paste into `ios/Runner/AppDelegate.swift` → `GMSServices.provideAPIKey("...")` in **both** apps.
  (Runtime map tiles for the Mapbox provider come from backend config, same as Android.)

## 7. Mapbox tokens — 🔑
- **Get it:** <https://account.mapbox.com/access-tokens/>:
  - A **secret downloads token** (`sk.…`, scope **`DOWNLOADS:READ`**) — needed by `pod install` to
    fetch the Mapbox iOS SDK. 📍 Put it in `~/.netrc` on the build Mac (see `IOS_SETUP.md` step 1),
    **not** in the repo.
  - The **public display token** (`pk.…`) — already managed in the admin panel (3rd-Party → Map) and
    used at runtime; `MGLMapboxAccessToken` in `Info.plist` is only a fallback.

## 8. Stripe — usually nothing new
- The **publishable key** you already use on Android works on iOS; it's passed at build time via
  `--dart-define=STRIPE_PUBLISHABLE_KEY=...` (see `IOS_SETUP.md` / CI). The **secret key** stays on the
  backend only.
- Only if you want **Apple Pay**: create an Apple **Merchant ID** (developer.apple.com → Identifiers →
  Merchant IDs) and enable the Apple Pay capability. Optional — not required to ship.

## 9. Universal links AASA file (optional, for `https://dacatlon.store/...` deep links)
- The entitlement `applinks:dacatlon.store` is set. For links to actually open the app, host a file at
  **`https://dacatlon.store/.well-known/apple-app-site-association`** (served as `application/json`,
  no redirect) containing both App IDs, e.g.:
  ```json
  { "applinks": { "apps": [], "details": [
    { "appID": "TEAMID.com.sixamtech.hexarideuser",  "paths": ["*"] },
    { "appID": "TEAMID.com.sixamtech.hexariderider", "paths": ["*"] }
  ] } }
  ```
  Replace `TEAMID` with your Team ID. Custom-scheme deep links work without this.

## 10. App Store Connect app records
- **Get it:** <https://appstoreconnect.apple.com> → **My Apps → +** → create one app per bundle ID
  (name, primary language, SKU). Fill in privacy nutrition labels, screenshots, description.

## 11. Signing certificate & provisioning profiles
- **Easiest:** Xcode **Automatic** signing (step 2) generates the Apple **Distribution certificate**
  and provisioning profiles for you.
- **Manual alternative:** developer.apple.com → **Certificates** (Apple Distribution) + **Profiles**
  (App Store profile per bundle ID), then select them in Xcode with Automatic signing off.

---

## Quick "what do I hand over" summary
| # | Item | Type | Repo/dest |
|---|------|------|-----------|
| 1 | Apple Developer membership | account | — |
| 2 | Team ID | 10-char string | Xcode Signing |
| 3 | 2× App IDs (Push + Associated Domains) | portal config | — |
| 4 | APNs `.p8` key + Key ID | file | Firebase → Cloud Messaging |
| 5 | 2× `GoogleService-Info.plist` | files | each app's `ios/Runner/` |
| 6 | Google Maps **iOS** key | string | `AppDelegate.swift` (both) |
| 7 | Mapbox `sk.…` downloads token | secret | `~/.netrc` on Mac |
| 8 | Stripe publishable key (existing) | string | `--dart-define` |
| 9 | AASA file (optional) | hosted json | `dacatlon.store/.well-known/` |
| 10 | 2× App Store Connect records | portal | — |
| 11 | Distribution cert + profiles | auto via Xcode | Xcode Signing |

Once 1–7 and 10–11 are in place: `flutter build ipa` on a Mac produces an uploadable archive.
