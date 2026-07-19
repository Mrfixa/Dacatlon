import java.util.Properties
import java.io.FileInputStream

plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("dev.flutter.flutter-gradle-plugin")
    id("com.google.gms.google-services")
}

val keystoreProperties = Properties()
val keystorePropertiesFile = rootProject.file("key.properties")
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(FileInputStream(keystorePropertiesFile))
}

android {
    namespace = "com.sixamtech.hexariderider"
    compileSdk = 36

    compileOptions {
        isCoreLibraryDesugaringEnabled = true
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    defaultConfig {
        multiDexEnabled = true
        applicationId = "com.sixamtech.hexariderider"
        minSdk = flutter.minSdkVersion
        targetSdk = 36
        versionCode = flutter.versionCode
        versionName = flutter.versionName
        // Android Google Maps key. Prefer the MAPS_API_KEY env var (CI secret); fall back to the
        // project's key so a plain `flutter build` (no secret) still renders map tiles instead of
        // grey. Android Maps keys are embedded in every APK by design — protect it by restricting
        // it to this app's package name + release SHA-1 in Google Cloud Console, NOT by secrecy.
        // NOTE: treat a blank env var the same as unset. CI always sets `MAPS_API_KEY:` in the build
        // step, so an empty repo secret makes getenv return "" (non-null) — `?:` would NOT fall back
        // and the manifest would ship an empty key → grey map tiles in the release APK.
        manifestPlaceholders["MAPS_API_KEY"] =
            System.getenv("MAPS_API_KEY").let { if (it.isNullOrBlank()) "AIzaSyCKoitvi1c7k_TRdynDVid68qk5W-vosr0" else it }
        manifestPlaceholders["MAPBOX_ACCESS_TOKEN"] = System.getenv("MAPBOX_ACCESS_TOKEN") ?: ""
    }

    signingConfigs {
        create("release") {
            keyAlias = keystoreProperties["keyAlias"] as String?
            keyPassword = keystoreProperties["keyPassword"] as String?
            storeFile = keystoreProperties["storeFile"]?.let { file(it as String) }
            storePassword = keystoreProperties["storePassword"] as String?
        }
        // Committed keystore used when no key.properties is provided. It gives
        // sideloaded builds a STABLE signature (updates install over previous
        // versions instead of failing signature-mismatch, as the per-runner
        // debug key did) and anchors .well-known/assetlinks.json. It provides
        // no security — never use it for a Play Store submission.
        create("sideload") {
            keyAlias = "vito"
            keyPassword = "vito-sideload"
            storeFile = file("sideload-keystore.jks")
            storePassword = "vito-sideload"
        }
    }

    buildTypes {
        getByName("release") {
            signingConfig = if (keystorePropertiesFile.exists()) {
                signingConfigs.getByName("release")
            } else {
                logger.warn(
                    "WARNING: key.properties not found — release APK signed with the committed " +
                    "sideload keystore. Fine for sideloading, but Google Play will reject it. " +
                    "Provide a private keystore (KEYSTORE_BASE64 secret in CI) before any Play submission."
                )
                signingConfigs.getByName("sideload")
            }
            isMinifyEnabled = false
            isShrinkResources = false
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_11
    }
}

flutter {
    source = "../.."
}


dependencies {
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.4")
    implementation("com.google.firebase:firebase-messaging:23.4.1")
}
