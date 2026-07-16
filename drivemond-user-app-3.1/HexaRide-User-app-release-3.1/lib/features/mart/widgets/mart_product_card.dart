import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_product_model.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_product_details_screen.dart';
import 'package:ride_sharing_user_app/helper/price_converter.dart';
import 'package:ride_sharing_user_app/util/app_colors.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Product card shared by the store grid, the Deals/Popular shelves and the
/// category listing: image with sale badge + stock chip, name, unit, and the
/// effective price with a struck-through original when on sale.
class MartProductCard extends StatefulWidget {
  final MartProductModel product;
  final bool isOffline;
  final void Function(MartProductModel product) onAdd;

  const MartProductCard({
    super.key,
    required this.product,
    required this.isOffline,
    required this.onAdd,
  });

  @override
  State<MartProductCard> createState() => _MartProductCardState();
}

class _MartProductCardState extends State<MartProductCard> {
  bool _isAdding = false;

  void _handleAdd() {
    if (widget.isOffline) {
      Get.snackbar('warning'.tr, 'you_are_offline'.tr);
      return;
    }
    setState(() => _isAdding = true);
    HapticFeedback.mediumImpact();
    widget.onAdd(widget.product);
    Future.delayed(const Duration(milliseconds: 120), () {
      if (mounted) setState(() => _isAdding = false);
    });
  }

  void _openDetails() {
    final id = widget.product.id;
    if (id == null || id.isEmpty) return;
    Get.to(() => MartProductDetailsScreen(
          productId: id,
          initialProduct: widget.product,
          allowAdd: !widget.isOffline,
        ));
  }

  @override
  Widget build(BuildContext context) {
    final product = widget.product;
    final imageUrl = product.image;
    final salePercent = product.onSale && product.price > 0
        ? (((product.price - product.effectivePrice) / product.price) * 100).round()
        : 0;

    return Card(
      elevation: 2,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 3,
            child: InkWell(
              onTap: _openDetails,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (imageUrl != null && imageUrl.isNotEmpty)
                    CachedNetworkImage(
                      imageUrl: imageUrl,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => Container(
                          color: Theme.of(context).hintColor.withValues(alpha: 0.1)),
                      errorWidget: (_, __, ___) => _imageFallback(context),
                    )
                  else
                    _imageFallback(context),
                  if (salePercent > 0)
                    Positioned(
                      top: 6,
                      left: 6,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.error,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text('-$salePercent%',
                            style: textBold.copyWith(
                                color: Colors.white,
                                fontSize: Dimensions.fontSizeExtraSmall)),
                      ),
                    ),
                  if (product.stock > 0)
                    Positioned(
                      top: 6,
                      right: 6,
                      child: _stockChip(context, product.stock),
                    ),
                ],
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Padding(
              padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name ?? '',
                    style: textMedium.copyWith(fontSize: Dimensions.fontSizeDefault),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (product.unit != null && product.unit!.isNotEmpty)
                    Text(
                      product.unit!,
                      style: textRegular.copyWith(
                        fontSize: Dimensions.fontSizeExtraSmall,
                        color: Theme.of(context).hintColor,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  const Spacer(),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Flexible(
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Flexible(
                              child: Text(
                                PriceConverter.convertPrice(product.effectivePrice),
                                style: textBold.copyWith(
                                  fontSize: Dimensions.fontSizeDefault,
                                  color: Theme.of(context).primaryColor,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (product.onSale) ...[
                              const SizedBox(width: 4),
                              Flexible(
                                child: Text(
                                  PriceConverter.convertPrice(product.price),
                                  style: textRegular.copyWith(
                                    fontSize: Dimensions.fontSizeExtraSmall,
                                    color: Theme.of(context).hintColor,
                                    decoration: TextDecoration.lineThrough,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      AnimatedScale(
                        scale: _isAdding ? 0.85 : 1.0,
                        duration: const Duration(milliseconds: 120),
                        child: InkWell(
                          onTap: _handleAdd,
                          borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                          child: Opacity(
                            opacity: widget.isOffline ? 0.5 : 1.0,
                            child: Container(
                              padding: const EdgeInsets.all(5),
                              decoration: BoxDecoration(
                                color: Theme.of(context).primaryColor,
                                borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                              ),
                              child: Icon(
                                _isAdding ? Icons.check : Icons.add,
                                color: Theme.of(context).colorScheme.onPrimary,
                                size: Dimensions.iconSizeSmall,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Low stock (1-5) shows an amber urgency chip; healthy stock a green one.
  Widget _stockChip(BuildContext context, int stock) {
    final low = stock <= 5;
    final color = low ? AppColors.ratingAmber : AppColors.successGreen;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        low ? '$stock ${'left_in_stock'.tr}' : 'in_stock'.tr,
        style: textBold.copyWith(fontSize: 10, color: color),
      ),
    );
  }

  Widget _imageFallback(BuildContext context) => Container(
        color: Theme.of(context).hintColor.withValues(alpha: 0.1),
        child: Center(
          child: Icon(Icons.inventory_2_outlined,
              size: 40, color: Theme.of(context).hintColor),
        ),
      );
}
