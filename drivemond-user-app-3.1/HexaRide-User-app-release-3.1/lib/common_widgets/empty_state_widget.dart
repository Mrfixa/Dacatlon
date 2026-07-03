import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/button_widget.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Consistent empty state widget with icon, message, and optional action button.
class EmptyStateWidget extends StatelessWidget {
  final String title;
  final String? description;
  final IconData icon;
  final String? buttonText;
  final VoidCallback? onButtonPressed;

  const EmptyStateWidget({
    super.key,
    required this.title,
    this.description,
    this.icon = Icons.inbox_outlined,
    this.buttonText,
    this.onButtonPressed,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 64, color: Theme.of(context).hintColor),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          Text(
            title,
            textAlign: TextAlign.center,
            style: textSemiBold.copyWith(
              color: Theme.of(context).textTheme.bodyLarge?.color,
              fontSize: Dimensions.fontSizeLarge,
            ),
          ),
          if (description != null) ...[
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Text(
              description!,
              textAlign: TextAlign.center,
              style: textRegular.copyWith(
                color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeDefault,
              ),
            ),
          ],
          if (buttonText != null && onButtonPressed != null) ...[
            const SizedBox(height: Dimensions.paddingSizeLarge),
            ButtonWidget(
              buttonText: buttonText!,
              width: 180,
              radius: 50,
              onPressed: onButtonPressed,
            ),
          ],
        ]),
      ),
    );
  }
}
