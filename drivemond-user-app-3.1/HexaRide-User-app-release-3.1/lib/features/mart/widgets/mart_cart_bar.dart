import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/mart/controllers/mart_controller.dart';
import 'package:ride_sharing_user_app/helper/price_converter.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Sticky bottom cart bar for the store: item count, live subtotal and a
/// "View cart" affordance. Hidden while the cart is empty.
class MartCartBar extends StatelessWidget {
  final VoidCallback onTap;

  const MartCartBar({super.key, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<MartController>(
      builder: (controller) {
        if (controller.cartItems.isEmpty) return const SizedBox.shrink();
        final onPrimary = Theme.of(context).colorScheme.onPrimary;
        return Material(
          color: Theme.of(context).primaryColor,
          child: InkWell(
            onTap: () {
              HapticFeedback.mediumImpact();
              onTap();
            },
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: Dimensions.paddingSizeLarge,
                  vertical: Dimensions.paddingSizeDefault,
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                      decoration: BoxDecoration(
                        color: onPrimary.withValues(alpha: 0.22),
                        borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                      ),
                      child: Text('${controller.cartItemCount}',
                          style: textBold.copyWith(color: onPrimary)),
                    ),
                    const SizedBox(width: Dimensions.paddingSizeSmall),
                    Text(
                      PriceConverter.convertPrice(controller.cartTotal),
                      style: textBold.copyWith(
                          color: onPrimary, fontSize: Dimensions.fontSizeDefault),
                    ),
                    const Spacer(),
                    Text('view_cart'.tr,
                        style: textBold.copyWith(
                            color: onPrimary, fontSize: Dimensions.fontSizeDefault)),
                    const SizedBox(width: 4),
                    Icon(Icons.arrow_forward, color: onPrimary, size: Dimensions.iconSizeSmall),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
