import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Quantity stepper shared by the product-detail CTA and cart line items.
class MartQtyStepper extends StatelessWidget {
  final int quantity;
  final ValueChanged<int> onChanged;
  final int min;
  final int max;

  const MartQtyStepper({
    super.key,
    required this.quantity,
    required this.onChanged,
    this.min = 1,
    this.max = 99,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: Theme.of(context).hintColor.withValues(alpha: 0.35)),
        borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _button(context, Icons.remove,
              quantity > min ? () => _change(quantity - 1) : null),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall),
            child: Text('$quantity',
                style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
          ),
          _button(context, Icons.add,
              quantity < max ? () => _change(quantity + 1) : null),
        ],
      ),
    );
  }

  void _change(int value) {
    HapticFeedback.selectionClick();
    onChanged(value);
  }

  Widget _button(BuildContext context, IconData icon, VoidCallback? onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
        child: Icon(
          icon,
          size: Dimensions.iconSizeMedium,
          color: onTap == null
              ? Theme.of(context).disabledColor
              : Theme.of(context).textTheme.bodyMedium?.color,
        ),
      ),
    );
  }
}
