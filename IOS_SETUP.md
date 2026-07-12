# iOS Build Setup (Vito User + Driver apps)

Both Flutter apps now ship a complete `ios/` project (ported from the original vendor source and
adapted to the current Vito codebase). iOS **must be built on a Mac** — there is no iOS toolchain
in CI (macOS runners are not enabled). This guide takes a clean checkout to a signed build.

| App | Path | Bundle ID | Display name |
|-----|------|-----------|--------------|
| User | `drivemond-user-app-3.1/HexaRide-User-app-release-3.1/ios` | `com.sixamtech.hexarideuser` | Vito |
| Driver | `drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/ios` | `com.sixamtech.hexariderider` | Vito Driver |

The iOS bundle IDs intentionally match each app's Android `applicationId`.

## Prerequisites
- macOS with **Xcode 15+** (Command Line Tools installed: `xcode-select --install`).
- **CocoaPods** (`sudo gem install cocoapods` or `brew install cocoapods`).
- Flutter SDK (same version used for the Android builds).

## 1. Mapbox pod authentication (required before `pod install`)
The apps depend on `mapbox_maps_flutter`, whose native SDK downloads from Mapbox's **private**
registry. Without a downloads token, `pod install` fails with a 401. Create a secret **downloads
token** (scope `DOWNLOADS:READ`) at <https://account.mapbox.com/access-tokens/> and add it to
`~/.netrc`:

```
machine api.mapbox.com
  login mapbox
  password sk.YOUR_SECRET_MAPBOX_DOWNLOADS_TOKEN
```

Then `chmod 600 ~/.netrc`. (This is separate from the public map-display token set in the admin
panel / `MGLMapboxAccessToken`.)

## 2. Install pods
Per app directory:

```bash
cd <app>/ios     # e.g. drivemond-user-app-3.1/HexaRide-User-app-release-3.1/ios
flutter pub get  # (run once from the app root; regenerates ios/Flutter/Generated.xcconfig)
pod install      # auto-resolves pods from the current pubspec (Stripe, Firebase, Maps, Mapbox…)
```

The `Podfile` is generic (`flutter_install_all_ios_pods`), so it always installs the pods matching
the app's current `pubspec.yaml` — no manual pod edits are needed when Flutter deps change.

## 3. Signing (one-time, in Xcode)
Open the **workspace** (not the project):

```bash
open ios/Runner.xcworkspace
```

- Select the **Runner** target → **Signing & Capabilities**.
- `DEVELOPMENT_TEAM` ships **blank** — pick your own Apple Developer team (Automatic signing is on).
- Bundle ID stays as the table above (or change it to your own; keep it in sync with your Firebase
  iOS app and your provisioning profile).

## 4. Firebase (push / analytics)
Each app ships a **placeholder** `ios/Runner/GoogleService-Info.plist` (its bundle ID matches, so
the project builds). Replace it with **your own** Firebase **iOS app** config, downloaded from the
**same** Firebase project you use for Android (`google-services.json`). Drag the real
`GoogleService-Info.plist` into `Runner/` in Xcode (replace, keep the filename). Until you do,
push notifications attach to the placeholder project, not yours.

## 5. Google Maps key (iOS)
The Maps SDK key is compiled into `ios/Runner/AppDelegate.swift`
(`GMSServices.provideAPIKey(...)`). It currently uses the shared project key. In the Google Cloud
Console, enable **Maps SDK for iOS** and restrict a key to each app's iOS bundle ID, then paste it
into `AppDelegate.swift`. (Runtime map tiles for Mapbox provider come from backend config, same as
Android.)

## 6. Build / run

```bash
flutter build ios --release            # signed archive-ready build
# or, to verify compilation without signing:
flutter build ios --no-codesign
# or run on a connected device / simulator:
flutter run
```

Produce an App Store archive with `flutter build ipa` (uses the team you set in step 3).

## What was adapted from the vendor source
- Display name → **Vito** / **Vito Driver** (matches the Android labels).
- Google Maps `AppDelegate` key set to the project key (was `YOUR_MAP_KEY_HERE`).
- Added `MGLMapboxAccessToken` to `Info.plist` for the Mapbox provider.
- Collapsed a duplicated `CFBundleURLTypes` key and dropped the vendor Google-Sign-In reversed
  client ID (neither app uses `google_sign_in`); kept a single deep-link URL scheme.
- Blanked the vendor `DEVELOPMENT_TEAM` so you sign with your own team.
- Removed the driver project's dangling Crashlytics "Upload Symbols" build phase (the current
  driver app has no `firebase_crashlytics` dependency, so that phase would fail the build).
- Excluded generated artifacts (`build/`, `Pods/`, `Flutter/ephemeral/`) — regenerated locally by
  the steps above.

## App icon (Vito branding)
The ported `ios/Runner/Assets.xcassets/AppIcon.appiconset` still contains the **vendor** artwork
(the display name is already "Vito"/"Vito Driver"). To fully brand iOS, replace the icon set with
the Vito logo — easiest is to add `flutter_launcher_icons` to `dev_dependencies`, point it at the
same source logo used for Android, and run `dart run flutter_launcher_icons` on the Mac (it writes
all iOS sizes), or drop a 1024×1024 PNG into the asset catalog via Xcode's app-icon editor.

## Outstanding user-supplied items (for a production build)
- Your own iOS `GoogleService-Info.plist` (step 4).
- Apple Developer **Team ID** + a distribution/provisioning profile (step 3).
- A Mapbox **downloads** token for `pod install` (step 1).
- An iOS-restricted Google Maps key (step 5).
- Vito app-icon artwork (see "App icon" above) — vendor icons ship as a placeholder.
