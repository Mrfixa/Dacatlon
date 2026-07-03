import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Screen for managing notification preferences.
class NotificationSettingsScreen extends StatefulWidget {
  const NotificationSettingsScreen({super.key});

  @override
  State<NotificationSettingsScreen> createState() => _NotificationSettingsScreenState();
}

class _NotificationSettingsScreenState extends State<NotificationSettingsScreen> {
  bool _rideNotifications = true;
  bool _orderNotifications = true;
  bool _promotionalNotifications = false;
  bool _chatNotifications = true;
  bool _soundEnabled = true;
  bool _vibrationEnabled = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'notification_settings'.tr, showBackButton: true),
      body: ListView(
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        children: [
          _buildSectionHeader('notification_types'.tr),
          _buildSwitchTile(
            title: 'ride_notifications'.tr,
            subtitle: 'receive_updates_about_your_rides'.tr,
            value: _rideNotifications,
            onChanged: (v) => setState(() => _rideNotifications = v),
          ),
          _buildSwitchTile(
            title: 'order_notifications'.tr,
            subtitle: 'receive_updates_about_mart_orders'.tr,
            value: _orderNotifications,
            onChanged: (v) => setState(() => _orderNotifications = v),
          ),
          _buildSwitchTile(
            title: 'promotional_notifications'.tr,
            subtitle: 'receive_promotions_and_offers'.tr,
            value: _promotionalNotifications,
            onChanged: (v) => setState(() => _promotionalNotifications = v),
          ),
          _buildSwitchTile(
            title: 'chat_notifications'.tr,
            subtitle: 'receive_messages_from_drivers'.tr,
            value: _chatNotifications,
            onChanged: (v) => setState(() => _chatNotifications = v),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          _buildSectionHeader('sound_vibration'.tr),
          _buildSwitchTile(
            title: 'sound'.tr,
            subtitle: 'play_sound_for_notifications'.tr,
            value: _soundEnabled,
            onChanged: (v) => setState(() => _soundEnabled = v),
          ),
          _buildSwitchTile(
            title: 'vibration'.tr,
            subtitle: 'vibrate_for_notifications'.tr,
            value: _vibrationEnabled,
            onChanged: (v) => setState(() => _vibrationEnabled = v),
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
