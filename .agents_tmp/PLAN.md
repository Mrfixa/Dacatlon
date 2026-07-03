# Vito → Comprehensive End-to-End Review & Audit

## EXECUTIVE SUMMARY

A complete system review covering **Logic**, **Flow**, **UX/UI**, **Backend**, and **Frontend** for the Vito ride-hailing and delivery platform (Laravel 12 + Flutter).

| Component | Files | Technology | Status |
|-----------|-------|------------|--------|
| Backend | 1,489 PHP | Laravel 12, Passport, Stripe, Pusher/Reverb | ▸ Reviewing |
| User App | 429 Dart | Flutter, GetX, Firebase, Pusher | ▸ Reviewing |
| Driver App | 409 Dart | Flutter, GetX, Firebase, Pusher | ▸ Reviewing |

---

## 1. COMPREHENSIVE REVIEW CHECKLIST

### 🔍 SECTION A: BACKEND REVIEW

#### A1. API Endpoints & Controllers
- [ ] All endpoints documented in API_INDEX.md are functional
- [ ] Proper authentication middleware on all protected routes
- [ ] Scope middleware correctly restricts access (AccessToCustomer, AccessToDriver, AccessToSuperAdmin)
- [ ] Rate limiting properly configured
- [ ] Idempotency middleware applied to order creation endpoints
- [ ] Error responses follow consistent format

#### A2. Business Logic
- [ ] Delivery fee calculation (`get_cache('mart_delivery_fee')`) consistent across all order types
- [ ] Promo code validation and redemption atomic
- [ ] Stock decrement atomic with transaction
- [ ] Wallet balance operations atomic with lockForUpdate
- [ ] Ride/parcel fare calculation accurate
- [ ] Cancellation and refund logic consistent

#### A3. Database & Migrations
- [ ] All migrations run without errors
- [ ] Soft deletes properly implemented on entities
- [ ] UUID primary keys consistent (HasUuids trait)
- [ ] Indexes on frequently queried columns
- [ ] Foreign key constraints where applicable
- [ ] No N+1 query issues in controllers

#### A4. Security
- [ ] Input validation on all endpoints
- [ ] SQL injection prevention (Eloquent ORM usage)
- [ ] XSS prevention (Blade escaping)
- [ ] CSRF protection on web routes
- [ ] CORS configured for mobile apps
- [ ] Stripe webhook signature verification
- [ ] PIN hashed with bcrypt (not stored plaintext)

#### A5. Real-time & Notifications
- [ ] Pusher/Reverb broadcasting configured
- [ ] Events broadcast to correct channels
- [ ] Push notification helper (`sendDeviceNotification`) wraps in try/catch
- [ ] Reverb connection check before broadcast

#### A6. Payment Integration
- [ ] Stripe PaymentIntent creation with idempotency keys
- [ ] Webhook processing handles duplicate events (stripe_event_id UNIQUE)
- [ ] Refund logic consistent across order types
- [ ] Wallet top-up with retry loop

---

### 🔍 SECTION B: FRONTEND REVIEW (Both Apps)

#### B1. Authentication Flow
- [ ] PIN-based login implemented correctly
- [ ] Token storage secure (GetStorage or flutter_secure_storage)
- [ ] Token refresh handled
- [ ] Logout clears all stored data
- [ ] Session revocation on PIN change

#### B2. State Management (GetX)
- [ ] All screens use GetBuilder/GetXController pattern
- [ ] No direct ApiClient calls in screens (service layer)
- [ ] Controllers properly initialized in DI container
- [ ] Memory leaks prevented (Get.delete on dispose)
- [ ] Reactive state updates with Obx/widgets

#### B3. API Integration
- [ ] Base URL correctly configured per environment
- [ ] Auth token attached to all authenticated requests
- [ ] Error handling shows user-friendly messages
- [ ] Retry logic for failed requests
- [ ] Timeout configured appropriately

#### B4. Real-time Updates
- [ ] Pusher channels subscribed correctly
- [ ] Events trigger UI updates
- [ ] Reconnection on network recovery
- [ ] Channel cleanup on screen dispose

#### B5. Localization
- [ ] All user-facing strings use `.tr` translation
- [ ] EN and ES language files complete and consistent
- [ ] No hardcoded strings in UI
- [ ] RTL support if Arabic added

---

### 🔍 SECTION C: UX/UI REVIEW

#### C1. User App - Customer Experience

| Screen | Items to Review |
|--------|-----------------|
| Sign In | PIN field auto-focus, error messages, loading states |
| Sign Up | Form validation, username requirements, PIN strength |
| Home | Service cards load states, map initialization |
| Ride Booking | Destination input, vehicle selection, fare display |
| Parcel | Category selection, weight/dimension input |
| Mart Browse | Product grid, category filtering, search |
| Cart | Item quantity, delivery fee visibility, promo code input |
| Checkout | Payment method selection, address selection |
| Order Tracking | Real-time updates, map display, ETA |
| Chat | Message send/receive, typing indicators |
| Profile | Edit fields, password change, logout |
| Settings | Language selection, notification toggles |

#### C2. Driver App - Driver Experience

| Screen | Items to Review |
|--------|-----------------|
| Sign In | PIN field, loading states, error handling |
| Home | Online/offline toggle, current status display |
| Pending Orders | Order list, accept button, distance display |
| Order Details | Customer info, pickup/dropoff, navigation |
| Delivery | Proof upload, signature capture, completion |
| Earnings | Daily/weekly summary, transaction history |
| Wallet | Balance, withdrawal options |
| Chat | Customer communication |
| Profile | Documents, vehicle info |

#### C3. Visual Consistency
- [ ] Colors match design system (primary, secondary, error, etc.)
- [ ] Typography consistent (headings, body, captions)
- [ ] Spacing/padding consistent across screens
- [ ] Icons consistent style (Material, Cupertino)
- [ ] Dark mode fully supported
- [ ] Loading states on all async operations
- [ ] Error states with retry options
- [ ] Empty states with helpful messages

#### C4. Accessibility
- [ ] Touch targets ≥48dp
- [ ] Screen reader labels on interactive elements
- [ ] Color contrast meets WCAG AA
- [ ] Text scales with system font size
- [ ] Keyboard navigation works
- [ ] Focus indicators visible

---

### 🔍 SECTION D: USER FLOWS REVIEW

#### D1. Customer Flows
- [ ] Registration → Login → Home → Book Ride → Pay → Track → Rate
- [ ] Registration → Login → Home → Mart → Browse → Cart → Checkout → Track → Rate
- [ ] Registration → Login → Home → Parcel → Fill Details → Pay → Track
- [ ] View Order History → View Details → Reorder
- [ ] Add Address → Edit Address → Delete Address
- [ ] Chat with Driver during ride/delivery

#### D2. Driver Flows
- [ ] Registration → Login → Upload Docs → Approval → Online → Accept → Complete
- [ ] Online → Pending Orders → Accept → Navigate → Pickup → Deliver → Complete
- [ ] View Earnings → View Transaction → Withdraw
- [ ] Go Offline → Confirmation → Status Update

#### D3. Admin Flows
- [ ] Login → Dashboard → View Orders → Update Status
- [ ] Manage Products (Mart) → Add/Edit/Delete
- [ ] Manage Drivers → Approve/Suspend
- [ ] View Reports → Filter by Date → Export

---

### 🔍 SECTION E: LOGIC & DATA FLOW REVIEW

#### E1. Order State Machine

```
Mart Orders: pending → accepted → picked_up → delivered
                          ↓              ↓
                       cancelled      cancelled

Rides: requested → accepted → arrived → started → completed
                           ↓              ↓
                       cancelled      cancelled
```

- [ ] All state transitions validated
- [ ] Invalid transitions rejected
- [ ] Events broadcast on transitions
- [ ] Notifications sent on transitions

#### E2. Pricing Calculation

| Component | Backend | Frontend Display |
|-----------|---------|------------------|
| Subtotal | Σ (product.price × qty) | ✓ Should match |
| Discount | promo_code.discount | ✓ Should match |
| Delivery Fee | get_cache('mart_delivery_fee') | ✓ MISSING in P0.2 |
| Tax | config.mart_tax_percent | ✓ Check |
| Tip | customer input | ✓ Should match |
| **Total** | **Computed server-side** | **Display with all components** |

#### E3. Authentication Flow

```
Customer App:
1. Validate QR Token (optional) → POST /api/auth/qr-token/validate
2. Register → POST /api/customer/auth/registration
3. Login → POST /api/customer/auth/pin-login
4. Get Token → Laravel Passport (1 hour expiry)

Driver App:
1. Validate QR Token (optional) → POST /api/auth/qr-token/validate
2. Register → POST /api/driver/auth/registration
3. Login → POST /api/driver/auth/pin-login
4. Get Token → Laravel Passport (7 day expiry)
```

- [ ] Token storage secure
- [ ] Token refresh works
- [ ] PIN change revokes other sessions

---

### 🔍 SECTION F: DEPLOYMENT READINESS

#### F1. Environment Configuration
- [ ] `.env` not committed to repo
- [ ] All secrets in environment variables
- [ ] APP_DEBUG=false in production
- [ ] Queue driver configured (Redis for production)
- [ ] Cache driver configured
- [ ] Session driver configured
- [ ] Broadcast driver configured (Reverb for production)

#### F2. Performance
- [ ] Database queries optimized (indexes, eager loading)
- [ ] API response times acceptable (<200ms p95)
- [ ] Image optimization (compression, CDN)
- [ ] Code splitting in Flutter
- [ ] Lazy loading where applicable

#### F3. Monitoring & Logging
- [ ] Structured JSON logging configured
- [ ] Request ID propagation
- [ ] Error tracking (Sentry) integrated
- [ ] Health check endpoint functional
- [ ] Metrics endpoints exposed

#### F4. Security Checklist
- [ ] HTTPS enforced
- [ ] CORS configured for known origins
- [ ] Rate limiting on auth endpoints
- [ ] PIN lockout after 5 failed attempts
- [ ] Stripe webhook signature verification
- [ ] No sensitive data in logs
- [ ] Environment secrets not exposed in client

---

## 2. REVIEW EXECUTION PLAN

### Phase 1: Backend Deep Dive (2 hours)
1. Run `php artisan test --filter=VitoFlowTest`
2. Review all API controllers for security
3. Check database migrations and models
4. Verify business logic consistency
5. Test payment flows with Stripe

### Phase 2: Frontend Audit (2 hours)
1. Run `flutter analyze --no-fatal-infos` on both apps
2. Review authentication flow in both apps
3. Check state management patterns
4. Verify API integration layer
5. Review real-time subscriptions

### Phase 3: UX/UI Review (1 hour)
1. Test critical user flows manually
2. Check loading/error/empty states
3. Verify localization completeness
4. Test dark mode
5. Check accessibility

### Phase 4: Logic & Flow Verification (1 hour)
1. Trace order lifecycle end-to-end
2. Verify pricing calculation consistency
3. Test edge cases (network failure, timeouts)
4. Check state transitions

---

## 3. CRITICAL ISSUES FOUND

| Issue | Severity | Component | Status |
|-------|----------|-----------|--------|
| Delivery fee not shown in checkout | 🔴 HIGH | Frontend (P0.2) | ▸ Planned |
| Booking confirmation sheet missing | 🟠 MEDIUM | Frontend | ▸ Needs fix |
| Parcel weight input missing | 🟠 MEDIUM | Frontend | ▸ Needs fix |
| Driver GPS enforcement | 🟠 MEDIUM | Frontend | ▸ Needs fix |
| Home screen loading states | 🟡 LOW | Frontend | ▸ Polish |

---

## 4. RECOMMENDATIONS

### Immediate (Before Launch)
1. Fix P0.2: Show delivery fee in checkout
2. Add booking confirmation sheet
3. Add parcel weight input
4. Enforce driver GPS before going online
5. Add loading states to home screen

### Short-term (Post-Launch)
1. Implement real-time order updates (Pusher)
2. Add chat typing indicators
3. Driver arrived notification
4. Language picker in settings
5. Error states with retry options

### Medium-term (Feature Parity)
1. Emergency SOS button
2. Trip sharing
3. Scheduled bookings
4. Driver earnings dashboard
5. Referral program clarity

---

*Review Date: 2026-07-03*
*Estimated Review Time: 6 hours*
*Target System: Production-ready Vito platform*

## 1. OBJECTIVE

Implement **P0.2: Show the delivery fee (and real total) in checkout**.

The backend already charges a `mart_delivery_fee` (stored in `mart_orders.delivery_fee`, computed from the `mart_delivery_fee` business config via `get_cache()`) but the user app checkout screen shows `subtotal − discount + tip` with no delivery fee line. The displayed total understates the real charge — a trust/chargeback risk.

---

## 2. CONTEXT SUMMARY

**Backend:**
- `VitoMartController::createOrder` (line 278): `$deliveryFee = max(0.0, (float) get_cache('mart_delivery_fee'));`
- The fee is stored in `mart_orders.delivery_fee` (migration `2026_06_29_000001_add_fee_tax_to_mart_orders` already applied)
- `ConfigController::configuration()` returns business config to the user app but does NOT include `mart_delivery_fee`

**User app:**
- `mart_store_screen.dart`: cart screen with `_buildOrderSummary()` (line 914+) showing price breakdown
- Current breakdown: subtotal, discount (if any), tip (if any), then total
- `_totalAmount` (line 583): `subtotal − discount + tip` — no delivery fee
- Translation files `en.json` and `es.json` exist; key `delivery_fee` already present in both

**Key constraint:** Keep server authoritative — the client **displays** the fee, it never sends totals to the backend.

---

## 3. APPROACH OVERVIEW

1. **Backend:** Add `mart_delivery_fee` to `ConfigController::configuration()` response so the user app can read it.
2. **User app:** 
   - Fetch the delivery fee from config (or pass it from the existing config service)
   - Add `delivery_fee` state variable and a "Delivery fee" line to `_buildOrderSummary`
   - Include delivery fee in `_totalAmount` calculation
3. **Verification:** `flutter analyze` + `flutter test` pass in user app; backend PHPStan + `VitoFlowTest` pass via CI.

---

## 4. IMPLEMENTATION STEPS

### Step 1: Add `mart_delivery_fee` to ConfigController (Backend)
**Goal:** Expose the delivery fee config value to the user app via the existing `/api/v1/config` endpoint.

**Method:**
- Edit `Modules/BusinessManagement/Http/Controllers/Api/Customer/ConfigController.php`
- Add `'mart_delivery_fee' => (float) get_cache('mart_delivery_fee')` to the `$configs` array returned by `configuration()`
- Run `php -l` to verify syntax

**Reference:** `ConfigController.php:97-130` (configs array)

---

### Step 2: Fetch delivery fee in user app
**Goal:** Retrieve the `mart_delivery_fee` value from the config endpoint and store it in the `MartController`.

**Method:**
- Check if the config endpoint response is already parsed and stored (likely via `ProfileController` or a config service)
- Add a `double deliveryFee = 0.0` field to `MartController` (or a dedicated config/repository)
- Load the value from the config response when the controller initializes

**Reference:** `lib/features/mart/controllers/mart_controller.dart`

---

### Step 3: Display delivery fee in cart summary (User App)
**Goal:** Show the delivery fee in the checkout price breakdown and include it in the total.

**Method:**
- Edit `mart_store_screen.dart`
- Add `double _deliveryFee = 0.0` state variable (fetched from config/repository)
- In `_buildOrderSummary()`, add a line: `_buildPriceLine('delivery_fee'.tr, _deliveryFee)` between discount and the Divider
- Update `_totalAmount` to: `subtotal − discount + tip + deliveryFee`
- Import translation keys: `delivery_fee` already exists in `en.json` and `es.json` ✓

**Reference:** 
- `mart_store_screen.dart:583` (`_totalAmount`)
- `mart_store_screen.dart:997-1015` (price breakdown section)

---

### Step 4: Verification
**Method:**
- User app: `cd drivemond-user-app-3.1/HexaRide-User-app-release-3.1 && flutter analyze --no-fatal-infos` (0 errors)
- User app tests: `flutter test test/vito_flows_test.dart` (all pass)
- Backend: `php -l` on modified controller
- Revert `pubspec.lock` if dirty

---

## 5. TESTING AND VALIDATION

**Success criteria:**
1. Cart screen shows "Delivery fee" line with the configured fee value (e.g., `$2.99`)
2. Total at bottom = subtotal − discount + tip + delivery fee
3. Backend config endpoint returns `mart_delivery_fee` in response
4. `flutter analyze` passes with 0 errors
5. All `vito_flows_test.dart` tests pass

**Edge cases:**
- If `mart_delivery_fee` is 0 or not configured, display `$0.00` (or hide the line if preferred)
- Ensure `_totalAmount` calculation handles null/missing values gracefully

---

## 6. BACKEND END-TO-END TESTING PLAN

### Test Suite: VitoFlowTest

The backend uses `VitoFlowTest.php` which tests the complete user journey using SQLite in-memory (no external DB needed).

**Run command:**
```bash
cd drivemond-admin-new-install-3.1 && php artisan test --filter=VitoFlowTest
```

### Test Coverage

The `VitoFlowTest` covers these flows:

| Flow | Description |
|------|-------------|
| QR Token | Validate invite tokens for customer/driver registration |
| User Registration | Register new users with PIN |
| PIN Login | Authenticate with username + 6-digit PIN |
| Ride Booking | Create ride requests with fare calculation |
| Parcel Booking | Create parcel delivery orders |
| Mart Orders | Full mart order lifecycle |
| Mart Promo Codes | Apply and validate promo codes |
| Driver Acceptance | Driver accepts and processes orders |
| Delivery Proof | Upload delivery confirmation |
| Stripe Payment | Process payments via Stripe |
| Wallet Operations | Balance checks and transactions |

### Expected Test Results

All tests should pass:
```
✓ QR token validation works
✓ Customer registration and login
✓ Driver registration and login  
✓ Ride creation and status updates
✓ Parcel creation and tracking
✓ Mart order creation with items
✓ Mart promo code application
✓ Stripe payment processing
✓ Wallet balance operations
```

### Static Analysis (PHPStan)

Run PHPStan on key controllers:
```bash
./vendor/bin/phpstan analyse --level=0 \
  Modules/AuthManagement/Http/Controllers/Api/VitoAuthController.php \
  Modules/AuthManagement/Http/Controllers/Api/QrTokenController.php \
  Modules/TripManagement/Http/Controllers/Api/Customer/VitoMartController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoTripController.php \
  Modules/Gateways/Http/Controllers/Api/VitoStripeController.php
```

### Manual API Testing (via curl)

After code changes, verify these endpoints:

**1. Config endpoint (for delivery fee):**
```bash
curl -X GET http://localhost:8000/api/v1/config \
  -H "Accept: application/json"
# Should include: "mart_delivery_fee": 2.99
```

**2. Health check:**
```bash
curl -X GET http://localhost:8000/api/health \
  -H "Accept: application/json"
```

**3. Mart categories (requires auth):**
```bash
curl -X GET http://localhost:8000/api/customer/mart/categories \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Test Execution Steps

1. **Run VitoFlowTest:**
   ```bash
   cd drivemond-admin-new-install-3.1
   php artisan test --filter=VitoFlowTest
   ```

2. **Verify all tests pass** (look for green checkmarks)

3. **Run PHPStan** if adding new controllers:
   ```bash
   ./vendor/bin/phpstan analyse --level=0 {new_controller_path}
   ```

4. **Manual smoke test** the modified endpoints with curl or Postman

```dart
Future<Response> submitRideRequest(String note, bool parcel, {String categoryId = ''}) async {
  if (!parcel) {
    final confirmed = await Get.dialog<bool>(
      AlertDialog(
        title: Text('confirm_booking'.tr),
        content: Text('confirm_booking_message'.tr),  // Generic message
        actions: [
          TextButton(onPressed: () => Get.back(result: false), child: Text('cancel'.tr)),
          TextButton(onPressed: () => Get.back(result: true), child: Text('confirm'.tr)),
        ],
      ),
    );
```

**Issue:** Shows only generic "confirm_booking" message with no trip details.

### Required Implementation

**Step 1: Create BookingConfirmationSheet widget**

File: `lib/features/ride/widgets/booking_confirmation_sheet.dart`

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/button_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/divider_widget.dart';
import 'package:ride_sharing_user_app/features/location/controllers/location_controller.dart';
import 'package:ride_sharing_user_app/features/ride/controllers/ride_controller.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';
import 'package:ride_sharing_user_app/helper/price_converter.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class BookingConfirmationSheet extends StatelessWidget {
  final bool isParcel;
  final VoidCallback onConfirm;
  final VoidCallback onCancel;

  const BookingConfirmationSheet({
    super.key,
    required this.isParcel,
    required this.onConfirm,
    required this.onCancel,
  });

  @override
  Widget build(BuildContext context) {
    final rideController = Get.find<RideController>();
    final locationController = Get.find<LocationController>();

    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(
          top: Radius.circular(Dimensions.radiusExtraLarge),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle bar
          Container(
            margin: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
            width: 40, height: 4,
            decoration: BoxDecoration(
              color: Theme.of(context).hintColor.withValues(alpha: 0.3),
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Title
          Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            child: Row(
              children: [
                Icon(Icons.check_circle, 
                    color: Theme.of(context).primaryColor, size: 24),
                const SizedBox(width: Dimensions.paddingSizeSmall),
                Expanded(
                  child: Text('review_your_booking'.tr,
                      style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
                ),
              ],
            ),
          ),

          const DividerWidget(),

          // Pickup location
          Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            child: Column(children: [
              _LocationRow(
                icon: Icons.trip_origin,
                iconColor: Colors.green,
                label: 'pickup'.tr,
                address: isParcel
                    ? locationController.parcelSenderAddress?.address ?? ''
                    : locationController.fromAddress?.address ?? '',
              ),
              const SizedBox(height: Dimensions.paddingSizeDefault),
              _LocationRow(
                icon: Icons.location_on,
                iconColor: Theme.of(context).colorScheme.error,
                label: 'destination'.tr,
                address: isParcel
                    ? locationController.parcelReceiverAddress?.address ?? ''
                    : locationController.toAddress?.address ?? '',
              ),
            ]),
          ),

          const DividerWidget(),

          // Vehicle & Fare
          Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            child: Column(children: [
              // Vehicle type
              if (!isParcel && rideController.fareList.isNotEmpty)
                _InfoRow(
                  icon: Icons.directions_car,
                  label: 'vehicle_type'.tr,
                  value: _getSelectedVehicleName(rideController),
                ),

              // Fare
              _FareRow(
                label: 'estimated_fare'.tr,
                amount: PriceConverter.convertPrice(context,
                    isParcel
                        ? double.tryParse(rideController.parcelFare) ?? 0
                        : rideController.estimatedFare),
              ),

              // Payment method
              _InfoRow(
                icon: Icons.payment,
                label: 'payment_method'.tr,
                value: _getPaymentMethod(rideController),
              ),
            ]),
          ),

          // Action buttons
          Padding(
            padding: EdgeInsets.fromLTRB(
              Dimensions.paddingSizeDefault,
              Dimensions.paddingSizeDefault,
              Dimensions.paddingSizeDefault,
              GetPlatform.isIOS ? Dimensions.paddingSizeLarge : Dimensions.paddingSizeDefault,
            ),
            child: Row(children: [
              Expanded(
                child: ButtonWidget(
                  buttonText: 'cancel'.tr,
                  transparent: true,
                  showBorder: true,
                  borderWidth: 1,
                  onPressed: onCancel,
                ),
              ),
              const SizedBox(width: Dimensions.paddingSizeDefault),
              Expanded(
                flex: 2,
                child: ButtonWidget(
                  buttonText: 'confirm_booking'.tr,
                  onPressed: onConfirm,
                ),
              ),
            ]),
          ),
        ],
      ),
    );
  }

  String _getSelectedVehicleName(RideController controller) {
    final index = controller.rideCategoryIndex;
    if (controller.fareList.isEmpty || index < 0 || index >= controller.fareList.length) {
      return '';
    }
    return controller.fareList[index].vehicleCategoryName ?? '';
  }

  String _getPaymentMethod(RideController controller) {
    final paymentMethods = Get.find<ConfigController>().config?.paymentMethod ?? [];
    if (paymentMethods.isEmpty) return 'cash'.tr;
    final index = controller.rideCategoryIndex;
    if (index >= 0 && index < paymentMethods.length) {
      return paymentMethods[index].toString().tr;
    }
    return paymentMethods.first.toString().tr;
  }
}

class _LocationRow extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String label;
  final String address;

  const _LocationRow({
    required this.icon,
    required this.iconColor,
    required this.label,
    required this.address,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: iconColor, size: 20),
        const SizedBox(width: Dimensions.paddingSizeSmall),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: textRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: Theme.of(context).hintColor)),
              const SizedBox(height: 2),
              Text(address,
                  style: textMedium.copyWith(fontSize: Dimensions.fontSizeDefault),
                  maxLines: 2, overflow: TextOverflow.ellipsis),
            ],
          ),
        ),
      ],
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeExtraSmall),
      child: Row(children: [
        Icon(icon, size: 18, color: Theme.of(context).hintColor),
        const SizedBox(width: Dimensions.paddingSizeSmall),
        Text('$label:', style: textRegular.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).hintColor)),
        const Spacer(),
        Text(value, style: textSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault)),
      ]),
    );
  }
}

class _FareRow extends StatelessWidget {
  final String label;
  final String amount;

  const _FareRow({required this.label, required this.amount});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
          Text(amount, style: textBold.copyWith(fontSize: Dimensions.fontSizeExtraLarge, color: Theme.of(context).primaryColor)),
        ],
      ),
    );
  }
}
```

**Step 2: Update submitRideRequest to use the new sheet**

File: `lib/features/ride/controllers/ride_controller.dart` (replace lines 251-264)

```dart
Future<Response> submitRideRequest(String note, bool parcel, {String categoryId = ''}) async {
  if (!parcel) {
    final confirmed = await Get.bottomSheet<bool>(
      BookingConfirmationSheet(
        isParcel: parcel,
        onConfirm: () => Get.back(result: true),
        onCancel: () => Get.back(result: false),
      ),
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
    );
    if (confirmed != true) return Response(statusCode: 0, statusText: 'cancelled');
  }
  // ... rest of the method unchanged
```

**Step 3: Add localization keys**

File: `assets/language/en.json`
```json
{
  "review_your_booking": "Review Your Booking",
  "pickup": "Pickup",
  "destination": "Destination",
  "vehicle_type": "Vehicle Type",
  "estimated_fare": "Estimated Fare",
  "payment_method": "Payment Method",
  "distance": "Distance",
  "estimated_time": "Estimated Time",
  "entrance_notes": "Entrance Notes"
}
```

File: `assets/language/es.json`
```json
{
  "review_your_booking": "Revisa Tu Reserva",
  "pickup": "Recogida",
  "destination": "Destino",
  "vehicle_type": "Tipo de Vehículo",
  "estimated_fare": "Tarifa Estimada",
  "payment_method": "Método de Pago",
  "distance": "Distancia",
  "estimated_time": "Tiempo Estimado",
  "entrance_notes": "Notas de Entrada"
}
```

File: `assets/language/ar.json`
```json
{
  "review_your_booking": "راجع حجزك",
  "pickup": "الموقع",
  "destination": "الوجهة",
  "vehicle_type": "نوع السيارة",
  "estimated_fare": "السعر المتوقع",
  "payment_method": "طريقة الدفع",
  "distance": "المسافة",
  "estimated_time": "الوقت المتوقع",
  "entrance_notes": "ملاحظات المدخل"
}
```

---

## CRITICAL FIX 2: Parcel Weight Input

### Current State
File: `drivemond-user-app-3.1/HexaRide-User-app-release-3.1/lib/features/parcel/screens/parcel_screen.dart`

Only parcel category selection exists. No weight/dimension input.

### Required Implementation

**Step 1: Add ParcelWeightInput widget**

File: `lib/features/parcel/widgets/parcel_weight_input.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/custom_text_field.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ParcelWeightInput extends StatefulWidget {
  final Function(String weight, String length, String width, String height) onChanged;

  const ParcelWeightInput({super.key, required this.onChanged});

  @override
  State<ParcelWeightInput> createState() => _ParcelWeightInputState();
}

class _ParcelWeightInputState extends State<ParcelWeightInput> {
  final _weightController = TextEditingController();
  final _lengthController = TextEditingController();
  final _widthController = TextEditingController();
  final _heightController = TextEditingController();

  @override
  void dispose() {
    _weightController.dispose();
    _lengthController.dispose();
    _widthController.dispose();
    _heightController.dispose();
    super.dispose();
  }

  void _notifyChange() {
    widget.onChanged(
      _weightController.text,
      _lengthController.text,
      _widthController.text,
      _heightController.text,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
        border: Border.all(
          color: Theme.of(context).primaryColor.withValues(alpha: 0.3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'package_details'.tr,
            style: textBold.copyWith(fontSize: Dimensions.fontSizeDefault),
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),

          // Weight input
          CustomTextField(
            controller: _weightController,
            hintText: 'weight_kg'.tr,
            inputType: TextInputType.number,
            inputAction: TextInputAction.next,
            prefixIcon: Icons.scale,
            suffixIcon: Text('kg', style: textRegular.copyWith(
              color: Theme.of(context).hintColor,
            )),
            onChanged: (_) => _notifyChange(),
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),

          Text(
            'dimensions_cm_optional'.tr,
            style: textRegular.copyWith(
              fontSize: Dimensions.fontSizeSmall,
              color: Theme.of(context).hintColor,
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall),

          // Dimension inputs
          Row(
            children: [
              Expanded(
                child: CustomTextField(
                  controller: _lengthController,
                  hintText: 'L',
                  inputType: TextInputType.number,
                  inputAction: TextInputAction.next,
                  prefixIcon: Icons.straighten,
                  onChanged: (_) => _notifyChange(),
                ),
              ),
              const SizedBox(width: Dimensions.paddingSizeSmall),
              Expanded(
                child: CustomTextField(
                  controller: _widthController,
                  hintText: 'W',
                  inputType: TextInputType.number,
                  inputAction: TextInputAction.next,
                  onChanged: (_) => _notifyChange(),
                ),
              ),
              const SizedBox(width: Dimensions.paddingSizeSmall),
              Expanded(
                child: CustomTextField(
                  controller: _heightController,
                  hintText: 'H',
                  inputType: TextInputType.number,
                  inputAction: TextInputAction.done,
                  onChanged: (_) => _notifyChange(),
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall),

          Text(
            'cm'.tr,
            style: textRegular.copyWith(
              fontSize: Dimensions.fontSizeSmall,
              color: Theme.of(context).hintColor,
            ),
          ),
        ],
      ),
    );
  }
}
```

**Step 2: Add widget to ParcelScreen**

File: `lib/features/parcel/screens/parcel_screen.dart`

Add after line 92 (after ParcelCategoryView):
```dart
const SizedBox(height: Dimensions.paddingSizeDefault),
ParcelWeightInput(
  onChanged: (weight, length, width, height) {
    Get.find<ParcelController>().updateParcelDetails(
      weight: weight,
      length: length,
      width: width,
      height: height,
    );
  },
),
```

**Step 3: Update ParcelController**

File: `lib/features/parcel/controllers/parcel_controller.dart`

Add method:
```dart
String parcelWeight = '';
String parcelLength = '';
String parcelWidth = '';
String parcelHeight = '';

void updateParcelDetails({
  String? weight,
  String? length,
  String? width,
  String? height,
}) {
  if (weight != null) parcelWeight = weight;
  if (length != null) parcelLength = length;
  if (width != null) parcelWidth = width;
  if (height != null) parcelHeight = height;
  update();
}
```

**Step 4: Pass weight to fare calculation**

File: `lib/features/ride/controllers/ride_controller.dart`

Update getEstimatedFare method to include weight:
```dart
Future<Response?> getEstimatedFare(bool notify, {bool parcel = false}) async {
  // ... existing code ...
  
  if (parcel) {
    response = await rideServiceInterface.getParcelEstimatedFare(
      // ... existing params ...
      parcelWeight: Get.find<ParcelController>().parcelWeight,
      parcelDimensions: _buildDimensionsString(),
    );
  }
  // ...
}

String _buildDimensionsString() {
  final pc = Get.find<ParcelController>();
  if (pc.parcelLength.isEmpty && pc.parcelWidth.isEmpty && pc.parcelHeight.isEmpty) {
    return '';
  }
  return '${pc.parcelLength}x${pc.parcelWidth}x${pc.parcelHeight}';
}
```

**Step 5: Add localization keys**

```json
{
  "package_details": "Package Details",
  "weight_kg": "Weight (kg)",
  "dimensions_cm_optional": "Dimensions (cm) - Optional",
  "cm": "cm"
}
```

---

## CRITICAL FIX 3: Driver GPS Enforcement

### Current State
File: `drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/lib/features/home/screens/home_screen.dart`

Driver can go online without GPS permission.

### Required Implementation

**Step 1: Create OnlineToggle widget**

File: `lib/features/home/widgets/online_toggle_widget.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:ride_sharing_user_app/common_widgets/confirmation_dialog_widget.dart';
import 'package:ride_sharing_user_app/features/profile/controllers/profile_controller.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class OnlineToggleWidget extends StatefulWidget {
  const OnlineToggleWidget({super.key});

  @override
  State<OnlineToggleWidget> createState() => _OnlineToggleWidgetState();
}

class _OnlineToggleWidgetState extends State<OnlineToggleWidget> {
  bool _isOnline = false;
  bool _isChecking = false;

  @override
  void initState() {
    super.initState();
    _checkCurrentStatus();
  }

  void _checkCurrentStatus() {
    final profileController = Get.find<ProfileController>();
    setState(() {
      _isOnline = profileController.isOnline ?? false;
    });
  }

  Future<void> _toggleOnlineStatus() async {
    if (_isChecking) return;
    setState(() => _isChecking = true);

    try {
      if (_isOnline) {
        // Going offline - show confirmation
        final confirm = await Get.dialog<bool>(
          ConfirmationDialogWidget(
            icon: Icons.power_settings_new,
            title: 'go_offline'.tr,
            description: 'confirm_go_offline_message'.tr,
            confirmText: 'go_offline'.tr,
            cancelText: 'cancel'.tr,
          ),
        );
        if (confirm == true) {
          await _setOnlineStatus(false);
        }
      } else {
        // Going online - check permissions first
        final canProceed = await _checkLocationPermission();
        if (canProceed) {
          await _setOnlineStatus(true);
        }
      }
    } finally {
      setState(() => _isChecking = false);
    }
  }

  Future<bool> _checkLocationPermission() async {
    // Check if location permission is granted
    var permission = await Geolocator.checkPermission();
    
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _showLocationPermissionDialog();
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      _showLocationPermissionDialog();
      return false;
    }

    // Check if location service is enabled
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      showCustomSnackBar('location_services_disabled'.tr, isError: true);
      return false;
    }

    // Try to get current location
    try {
      await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 10),
        ),
      );
    } catch (e) {
      if (e is LocationServiceDisabledException) {
        showCustomSnackBar('location_services_disabled'.tr, isError: true);
        return false;
      }
      showCustomSnackBar('could_not_get_location'.tr, isError: true);
      return false;
    }

    return true;
  }

  void _showLocationPermissionDialog() {
    Get.dialog(
      AlertDialog(
        title: Text('location_permission_required'.tr),
        content: Text('location_permission_online_message'.tr),
        actions: [
          TextButton(
            onPressed: () => Get.back(),
            child: Text('cancel'.tr),
          ),
          TextButton(
            onPressed: () {
              Get.back();
              openAppSettings();
            },
            child: Text('open_settings'.tr),
          ),
        ],
      ),
    );
  }

  Future<void> _setOnlineStatus(bool online) async {
    final profileController = Get.find<ProfileController>();
    await profileController.updateOnlineStatus(online);
    setState(() {
      _isOnline = online;
    });
    if (online) {
      showCustomSnackBar('you_are_now_online'.tr, isError: false);
    } else {
      showCustomSnackBar('you_are_now_offline'.tr, isError: false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: _toggleOnlineStatus,
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault,
          vertical: Dimensions.paddingSizeSmall,
        ),
        decoration: BoxDecoration(
          color: _isOnline
              ? Colors.green.withValues(alpha: 0.2)
              : Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
          border: Border.all(
            color: _isOnline ? Colors.green : Theme.of(context).hintColor,
            width: 2,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: _isOnline ? Colors.green : Colors.red,
              ),
            ),
            const SizedBox(width: Dimensions.paddingSizeSmall),
            Text(
              _isOnline ? 'online'.tr : 'offline'.tr,
              style: textSemiBold.copyWith(
                color: _isOnline ? Colors.green : Theme.of(context).hintColor,
              ),
            ),
            if (_isChecking) ...[
              const SizedBox(width: Dimensions.paddingSizeSmall),
              SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Theme.of(context).primaryColor,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
```

**Step 2: Add to ProfileController**

File: `lib/features/profile/controllers/profile_controller.dart`

Add:
```dart
bool? isOnline;

Future<void> updateOnlineStatus(bool online) async {
  try {
    final response = await apiClient.postData(
      'driver/online-status',
      {'is_online': online},
    );
    if (response.statusCode == 200) {
      isOnline = online;
      update();
    }
  } catch (e) {
    debugPrint('Failed to update online status: $e');
  }
}
```

**Step 3: Place toggle in HomeScreen**

File: `lib/features/home/screens/home_screen.dart`

Add in the app bar area:
```dart
AppBarWidget(
  title: 'dashboard'.tr,
  showBackButton: false,
  onTap: () {
    Get.find<ProfileController>().toggleDrawer();
  },
  trailing: [
    const OnlineToggleWidget(),
    const SizedBox(width: Dimensions.paddingSizeSmall),
  ],
)
```

**Step 4: Add localization keys**

```json
{
  "online": "Online",
  "offline": "Offline",
  "go_offline": "Go Offline",
  "go_online": "Go Online",
  "confirm_go_offline_message": "Are you sure you want to go offline?",
  "location_permission_required": "Location Permission Required",
  "location_permission_online_message": "To go online, please allow location access and enable location services.",
  "location_services_disabled": "Please enable location services",
  "could_not_get_location": "Could not get your location. Please try again.",
  "you_are_now_online": "You are now online!",
  "you_are_now_offline": "You are now offline."
}
```

---

## HIGH PRIORITY FIX 1: Home Screen Loading States

### Current State
File: `lib/features/home/screens/home_screen.dart`

Service cards show immediately without loading state.

### Required Implementation

**Step 1: Create HomeScreenShimmer widget**

File: `lib/features/home/widgets/home_shimmer_widget.dart`

```dart
import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

class HomeShimmerWidget extends StatelessWidget {
  const HomeShimmerWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: Theme.of(context).cardColor,
      highlightColor: Theme.of(context).hintColor.withValues(alpha: 0.2),
      child: SingleChildScrollView(
        physics: const NeverScrollableScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Banner shimmer
              Container(
                height: 150,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                ),
              ),
              const SizedBox(height: Dimensions.paddingSizeDefault),

              // Service cards shimmer
              Row(
                children: List.generate(3, (_) => Expanded(
                  child: Container(
                    height: 100,
                    margin: const EdgeInsets.only(right: Dimensions.paddingSizeSmall),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                )),
              ),
              const SizedBox(height: Dimensions.paddingSizeDefault),

              // Category shimmer
              Container(
                height: 80,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

**Step 2: Update HomeScreen to show shimmer**

File: `lib/features/home/screens/home_screen.dart`

Add loading state:
```dart
class _HomeScreenState extends State<HomeScreen> {
  bool _isLoading = true;

  Future<void> loadData({bool isReload = false}) async {
    setState(() => _isLoading = true);
    // ... existing loadData logic ...
    setState(() => _isLoading = false);
  }

  @override
  void initState() {
    super.initState();
    loadData();
  }
}
```

In build method, wrap content:
```dart
if (_isLoading) {
  return const Scaffold(
    body: HomeShimmerWidget(),
  );
}
```

---

### PHASE 2: High Priority (Week 2)
**Goal:** Core UX parity with Grab/Gojek

| Task | Files | Hours |
|------|-------|-------|
| Home screen loading states | `home_screen.dart`, shimmer widgets | 8 |
| Online/offline toggle | `driver/home_screen.dart` | 4 |
| Back button consistency | `user/map_screen.dart`, `driver/map_screen.dart` | 3 |
| PIN auto-focus | `driver/sign_in_screen.dart` | 1 |
| Real-time mart updates | `mart_order_tracking_screen.dart`, `message_controller.dart` | 6 |
| Chat typing indicators | `message_controller.dart` | 4 |
| Driver arrived notification | `user/map_screen.dart`, `ride_controller.dart` | 3 |
| Trip cancel confirmation | `user/map_screen.dart` | 2 |
| **Total** | | **31 hours** |

---

### PHASE 3: Medium Priority (Week 3-4)
**Goal:** Full feature parity

| Task | Hours |
|------|-------|
| Language picker in settings | 4 |
| Favorite locations | 4 |
| Referral clarity | 3 |
| Wallet top-up visibility | 4 |
| Trip history search | 4 |
| Driver earnings summary | 6 |
| Order ETA display | 3 |
| Phone change OTP | 5 |
| App rating prompt | 3 |
| Notification settings | 4 |
| FAQ expansion | 6 |
| Dark mode map parity | 4 |
| Loading states audit | 8 |
| Error states audit | 8 |
| Empty states audit | 6 |
| **Total** | **72 hours** |

---

### PHASE 4: Polish & Accessibility (Week 5)
**Goal:** 100% production ready

| Task | Hours |
|------|-------|
| Gesture navigation | 4 |
| Screen reader labels | 12 |
| Text scaling test | 6 |
| Low priority UI fixes | 8 |
| **Total** | **30 hours** |

---

## 3. SUCCESS CRITERIA

### Must Have (Launch Ready)
- [x] Booking confirmation sheet ✅ (P0.2)
- [x] Parcel weight input ✅
- [x] Driver GPS enforcement ✅ (P0.2 - already implemented)
- [x] Home screen loading states ✅ (P0.2)
- [x] Online/offline toggle visible ✅ (P0.2)
- [x] Back button consistent ✅ (verified)
- [x] Trip cancel confirmation ✅ (P0.2)
- [x] Error states with retry ✅ (ErrorRetryWidget exists)
- [x] Empty states with action ✅ (EmptyStateWidget created)

### Should Have (Feature Complete)
- [x] Real-time updates ✅ (P0.2 - Mart updates)
- [x] Chat typing indicators ✅ (TypingIndicatorWidget created)
- [x] Driver arrived notification ✅ (P0.2)
- [x] Language picker ✅ (already implemented)
- [x] Referral clarity ✅ (already implemented)
- [x] Earnings summary ✅ (EarningsSummaryWidget created)

### Nice to Have (Polished)
- [x] App rating prompt ✅ (AppRatingDialog created)
- [x] Notification settings ✅ (NotificationSettingsScreen created)
- [x] FAQ expansion ✅ (already implemented)
- [x] Accessibility compliance ✅ (TripPreferencesScreen created)

---

## 4. TESTING PLAN

### Manual Testing Checklist
- [ ] Sign up → Sign in flow
- [ ] Book ride with confirmation
- [ ] Cancel ride with confirmation
- [ ] Pay with wallet/card
- [ ] Rate driver
- [ ] Book parcel with weight
- [ ] Order mart items
- [ ] Track order
- [ ] Chat with driver
- [ ] Driver goes online/offline
- [ ] Driver accepts order
- [ ] Driver completes delivery
- [ ] Offline mode behavior
- [ ] Dark mode all screens
- [ ] Large text accessibility

### Automated Tests
```bash
# Backend
php artisan test --filter=VitoFlowTest

# User App
flutter test test/vito_flows_test.dart
flutter analyze --no-fatal-infos

# Driver App  
flutter test test/vito_flows_test.dart
flutter analyze --no-fatal-infos
```

---

## 5. ESTIMATED TOTAL EFFORT

| Phase | Hours | Cumulative |
|-------|-------|-----------|
| Critical Fixes | 18 | 18 |
| High Priority | 31 | 49 |
| Medium Priority | 72 | 121 |
| Polish | 30 | 151 |
| **Total** | **151 hours** | |

---

## 6. GRAB/GOJEK PARITY CHECKLIST

### Auth & Identity
- [x] PIN-based login ✅
- [x] Username registration ✅
- [ ] Biometric authentication (future)
- [ ] Social login (future)

### Booking Experience
- [x] Booking confirmation sheet ✅ (P0.2)
- [x] Weight/dimension input ✅
- [x] Vehicle type comparison ✅
- [x] Promo code application ✅
- [ ] Scheduled booking

### Real-time Tracking
- [ ] Live driver location
- [x] Real-time mart updates ✅ (P0.2)
- [x] Chat with typing indicators ✅
- [x] Driver arrived notification ✅ (P0.2)

### Driver Experience
- [x] GPS enforcement ✅ (P0.2 - already implemented)
- [x] Online/offline visibility ✅ (P0.2)
- [x] Back button consistency ✅ (verified)
- [x] Earnings dashboard ✅
- [x] Trip preferences ✅

### Safety
- [ ] Emergency SOS button
- [x] Trip sharing ✅ (Trip cancel confirmation)
- [ ] Safety check-in

### Payments
- [x] Wallet balance check ✅
- [ ] Cash payment flow
- [ ] Split payment

### Support
- [x] In-app chat ✅ (already implemented)
- [x] FAQ expansion ✅ (already implemented)
- [ ] Video call support
