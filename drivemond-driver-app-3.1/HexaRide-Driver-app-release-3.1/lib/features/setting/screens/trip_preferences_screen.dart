import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/button_widget.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Screen for managing driver trip preferences.
class TripPreferencesScreen extends StatefulWidget {
  const TripPreferencesScreen({super.key});

  @override
  State<TripPreferencesScreen> createState() => _TripPreferencesScreenState();
}

class _TripPreferencesScreenState extends State<TripPreferencesScreen> {
  bool _acceptRides = true;
  bool _acceptParcels = true;
  bool _acceptMartOrders = true;
  bool _preferShortTrips = false;
  bool _preferLongTrips = false;
  bool _nightMode = false;
  double _maxDistance = 10.0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'trip_preferences'.tr, showBackButton: true),
      body: ListView(
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        children: [
          _buildSectionHeader('service_type'.tr),
          _buildSwitchTile(
            title: 'accept_rides'.tr,
            subtitle: 'receive_ride_requests'.tr,
            value: _acceptRides,
            onChanged: (v) => setState(() => _acceptRides = v),
          ),
          _buildSwitchTile(
            title: 'accept_parcels'.tr,
            subtitle: 'receive_parcel_delivery_requests'.tr,
            value: _acceptParcels,
            onChanged: (v) => setState(() => _acceptParcels = v),
          ),
          _buildSwitchTile(
            title: 'accept_mart_orders'.tr,
            subtitle: 'receive_mart_order_delivery_requests'.tr,
            value: _acceptMartOrders,
            onChanged: (v) => setState(() => _acceptMartOrders = v),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          _buildSectionHeader('trip_preferences'.tr),
          _buildSwitchTile(
            title: 'prefer_short_trips'.tr,
            subtitle: 'prioritize_short_distance_trips'.tr,
            value: _preferShortTrips,
            onChanged: (v) {
              setState(() {
                _preferShortTrips = v;
                if (v) _preferLongTrips = false;
              });
            },
          ),
          _buildSwitchTile(
            title: 'prefer_long_trips'.tr,
            subtitle: 'prioritize_long_distance_trips'.tr,
            value: _preferLongTrips,
            onChanged: (v) {
              setState(() {
                _preferLongTrips = v;
                if (v) _preferShortTrips = false;
              });
            },
          ),
          _buildSwitchTile(
            title: 'night_mode'.tr,
            subtitle: 'enable_night_mode_for_driving'.tr,
            value: _nightMode,
            onChanged: (v) => setState(() => _nightMode = v),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          _buildSectionHeader('max_distance'.tr),
          Container(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8)],
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('${'accept_requests_within'.tr} ${_maxDistance.toInt()} km'),
                  ],
                ),
                Slider(
                  value: _maxDistance,
                  min: 1,
                  max: 50,
                  divisions: 49,
                  onChanged: (v) => setState(() => _maxDistance = v),
                ),
              ],
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          ButtonWidget(
            buttonText: 'save_preferences'.tr,
            onPressed: () {
              Get.snackbar('success'.tr, 'preferences_saved'.tr, snackPosition: SnackPosition.BOTTOM);
              Get.back();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
      child: Text(
        title,
        style: textSemiBold.copyWith(fontSize: Dimensions.fontSizeLarge),
      ),
    );
  }

  Widget _buildSwitchTile({
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8)],
      ),
      child: SwitchListTile(
        title: Text(title, style: textRegular),
        subtitle: Text(subtitle, style: textRegular.copyWith(
          color: Theme.of(context).hintColor,
          fontSize: Dimensions.fontSizeSmall,
        )),
        value: value,
        onChanged: onChanged,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault,
          vertical: Dimensions.paddingSizeSmall,
        ),
      ),
    );
  }
}
