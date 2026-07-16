import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/features/mart/controllers/mart_controller.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_product_model.dart';
import 'package:ride_sharing_user_app/features/mart/widgets/mart_qty_stepper.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/helper/price_converter.dart';
import 'package:ride_sharing_user_app/util/app_colors.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Product page: hero image with a favorite heart, honest pricing (effective
/// price, struck-through original, "Save %"), stock chip, description, the
/// delivery-fee line, and a quantity stepper wired into the add-to-cart CTA.
class MartProductDetailsScreen extends StatefulWidget {
  final String productId;
  final MartProductModel? initialProduct;
  final bool allowAdd;

  const MartProductDetailsScreen({
    super.key,
    required this.productId,
    this.initialProduct,
    this.allowAdd = true,
  });

  @override
  State<MartProductDetailsScreen> createState() => _MartProductDetailsScreenState();
}

class _MartProductDetailsScreenState extends State<MartProductDetailsScreen> {
  int _quantity = 1;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final controller = Get.find<MartController>();
      controller.getProductDetails(widget.productId);
      // The heart needs the favorite set; load it once if nothing is cached.
      if (controller.favoriteIds.isEmpty) {
        controller.getFavorites();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'product_details'.tr),
      body: GetBuilder<MartController>(
        builder: (martController) {
          final product = martController.productDetails ?? widget.initialProduct;
          if (martController.isLoading && product == null) {
            return const Center(child: CircularProgressIndicator());
          }
          if (product == null) {
            return Center(child: Text('something_went_wrong'.tr));
          }
          return ListView(
            padding: EdgeInsets.zero,
            children: [
              _buildHero(context, martController, product),
              Padding(
                padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(product.name ?? '',
                        style: textBold.copyWith(fontSize: Dimensions.fontSizeExtraLarge)),
                    const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                    _buildSubline(context, product),
                    const SizedBox(height: Dimensions.paddingSizeDefault),
                    _buildPriceRow(context, product),
                    if (product.description != null && product.description!.isNotEmpty) ...[
                      const SizedBox(height: Dimensions.paddingSizeDefault),
                      Text('description'.tr, style: textBold),
                      const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                      Text(product.description!,
                          style: textRegular.copyWith(color: Theme.of(context).disabledColor)),
                    ],
                    const SizedBox(height: Dimensions.paddingSizeDefault),
                    _buildDeliveryFeeLine(context, martController),
                  ],
                ),
              ),
            ],
          );
        },
      ),
      bottomNavigationBar: !widget.allowAdd
          ? null
          : GetBuilder<MartController>(
              builder: (martController) {
                final product = martController.productDetails ?? widget.initialProduct;
                if (product == null) return const SizedBox.shrink();
                return SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                    child: Row(
                      children: [
                        MartQtyStepper(
                          quantity: _quantity,
                          onChanged: (value) => setState(() => _quantity = value),
                        ),
                        const SizedBox(width: Dimensions.paddingSizeDefault),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () => _addToCart(product),
                            style: ElevatedButton.styleFrom(
                              minimumSize: const Size(double.infinity, 50),
                              backgroundColor: Theme.of(context).primaryColor,
                              foregroundColor: Theme.of(context).colorScheme.onPrimary,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                              ),
                            ),
                            child: Text(
                              '${'add_to_cart'.tr} · ${PriceConverter.convertPrice(product.effectivePrice * _quantity)}',
                              style: textBold.copyWith(
                                  color: Theme.of(context).colorScheme.onPrimary),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
    );
  }

  void _addToCart(MartProductModel product) {
    HapticFeedback.mediumImpact();
    Get.find<MartController>().addToCart({
      'id': product.id,
      'name': product.name,
      'price': product.effectivePrice,
      'image': product.image,
    }, quantity: _quantity);
    showCustomSnackBar('item_added_to_cart'.tr, isError: false);
    Get.back();
  }

  Widget _buildHero(BuildContext context, MartController controller, MartProductModel product) {
    return Stack(
      children: [
        AspectRatio(
          aspectRatio: 16 / 11,
          child: (product.image != null && product.image!.isNotEmpty)
              ? CachedNetworkImage(
                  imageUrl: product.image!,
                  fit: BoxFit.cover,
                  errorWidget: (_, __, ___) => _placeholder(context),
                )
              : _placeholder(context),
        ),
        // Favorite heart (optimistic toggle on the controller).
        if (product.id != null)
          Positioned(
            top: Dimensions.paddingSizeDefault,
            right: Dimensions.paddingSizeDefault,
            child: Material(
              color: Theme.of(context).cardColor,
              shape: const CircleBorder(),
              elevation: 2,
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: () {
                  HapticFeedback.selectionClick();
                  controller.toggleFavorite(product.id!);
                },
                child: Padding(
                  padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                  child: Icon(
                    controller.isFavorite(product.id) ? Icons.favorite : Icons.favorite_border,
                    color: controller.isFavorite(product.id)
                        ? Theme.of(context).colorScheme.error
                        : Theme.of(context).hintColor,
                    size: Dimensions.iconSizeMedium,
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  /// Category · unit · stock chip.
  Widget _buildSubline(BuildContext context, MartProductModel product) {
    final parts = <String>[
      if (product.category != null && product.category!.isNotEmpty) product.category!,
      if (product.unit != null && product.unit!.isNotEmpty) product.unit!,
    ];
    return Row(
      children: [
        if (parts.isNotEmpty)
          Flexible(
            child: Text(
              parts.join(' · '),
              style: textRegular.copyWith(color: Theme.of(context).hintColor),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        if (product.stock > 0) ...[
          const SizedBox(width: Dimensions.paddingSizeSmall),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(999),
              border: Border.all(
                color: (product.stock <= 5 ? AppColors.ratingAmber : AppColors.successGreen)
                    .withValues(alpha: 0.5),
              ),
            ),
            child: Text(
              product.stock <= 5 ? '${product.stock} ${'left_in_stock'.tr}' : 'in_stock'.tr,
              style: textBold.copyWith(
                fontSize: 10,
                color: product.stock <= 5 ? AppColors.ratingAmber : AppColors.successGreen,
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildPriceRow(BuildContext context, MartProductModel product) {
    final salePercent = product.onSale && product.price > 0
        ? (((product.price - product.effectivePrice) / product.price) * 100).round()
        : 0;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Text(PriceConverter.convertPrice(product.effectivePrice),
            style: textBold.copyWith(
                color: Theme.of(context).primaryColor,
                fontSize: Dimensions.fontSizeExtraLarge)),
        if (product.onSale) ...[
          const SizedBox(width: 8),
          Padding(
            padding: const EdgeInsets.only(bottom: 2),
            child: Text(PriceConverter.convertPrice(product.price),
                style: textRegular.copyWith(
                  color: Theme.of(context).hintColor,
                  decoration: TextDecoration.lineThrough,
                )),
          ),
          const Spacer(),
          if (salePercent > 0)
            Text(
              '${'save'.tr} $salePercent%',
              style: textBold.copyWith(
                  color: Theme.of(context).colorScheme.error,
                  fontSize: Dimensions.fontSizeSmall),
            ),
        ],
      ],
    );
  }

  /// Delivery-fee transparency: free-delivery callout or the flat fee.
  Widget _buildDeliveryFeeLine(BuildContext context, MartController controller) {
    final fee = controller.deliveryFee;
    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeSmall),
      decoration: BoxDecoration(
        color: Theme.of(context).primaryColor.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
        border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          Icon(Icons.delivery_dining,
              color: Theme.of(context).primaryColor, size: Dimensions.iconSizeMedium),
          const SizedBox(width: Dimensions.paddingSizeSmall),
          Expanded(
            child: Text(
              fee <= 0
                  ? 'free_delivery_today'.tr
                  : '${'delivery_fee'.tr}: ${PriceConverter.convertPrice(fee)}',
              style: textMedium.copyWith(fontSize: Dimensions.fontSizeSmall),
            ),
          ),
        ],
      ),
    );
  }

  Widget _placeholder(BuildContext context) => Container(
        color: Theme.of(context).disabledColor.withValues(alpha: 0.1),
        child: Icon(Icons.shopping_bag_outlined,
            size: 56, color: Theme.of(context).disabledColor),
      );
}
