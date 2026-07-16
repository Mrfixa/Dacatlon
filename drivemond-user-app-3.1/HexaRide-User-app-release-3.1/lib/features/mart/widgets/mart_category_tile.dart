import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Icon tile used in the home category rail and the all-categories grid.
/// Shows the category image when the admin uploaded one, otherwise a
/// deterministic fallback icon so the rail never renders blank squares.
class MartCategoryTile extends StatelessWidget {
  final String name;
  final String? image;
  final bool selected;
  final VoidCallback onTap;
  final int iconSeed;
  final double size;

  const MartCategoryTile({
    super.key,
    required this.name,
    required this.onTap,
    this.image,
    this.selected = false,
    this.iconSeed = 0,
    this.size = 64,
  });

  static const List<IconData> _fallbackIcons = [
    Icons.storefront_outlined,
    Icons.eco_outlined,
    Icons.egg_outlined,
    Icons.set_meal_outlined,
    Icons.bakery_dining_outlined,
    Icons.kitchen_outlined,
    Icons.ac_unit_outlined,
    Icons.local_drink_outlined,
    Icons.cleaning_services_outlined,
    Icons.child_care_outlined,
    Icons.medication_outlined,
    Icons.shopping_basket_outlined,
  ];

  @override
  Widget build(BuildContext context) {
    final primary = Theme.of(context).primaryColor;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
      child: SizedBox(
        width: size + 14,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: size,
              height: size,
              decoration: BoxDecoration(
                color: selected
                    ? primary.withValues(alpha: 0.15)
                    : Theme.of(context).hintColor.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(Dimensions.radiusDefault + 4),
                border: Border.all(
                  color: selected
                      ? primary.withValues(alpha: 0.5)
                      : Theme.of(context).hintColor.withValues(alpha: 0.15),
                ),
              ),
              child: (image != null && image!.isNotEmpty)
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault + 3),
                      child: CachedNetworkImage(
                        imageUrl: image!,
                        fit: BoxFit.cover,
                        errorWidget: (_, __, ___) => _fallbackIcon(context),
                      ),
                    )
                  : _fallbackIcon(context),
            ),
            const SizedBox(height: 5),
            Text(
              name == 'all' ? 'all'.tr : name,
              style: (selected ? textMedium : textRegular).copyWith(
                fontSize: Dimensions.fontSizeExtraSmall,
                color: selected ? primary : Theme.of(context).hintColor,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _fallbackIcon(BuildContext context) => Icon(
        _fallbackIcons[iconSeed % _fallbackIcons.length],
        size: size * 0.44,
        color: selected
            ? Theme.of(context).primaryColor
            : Theme.of(context).hintColor,
      );
}
