import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:shimmer/shimmer.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/features/mart/controllers/mart_controller.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_product_model.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_cart_screen.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_categories_screen.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_favorites_screen.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_order_history_screen.dart';
import 'package:ride_sharing_user_app/features/mart/widgets/mart_cart_bar.dart';
import 'package:ride_sharing_user_app/features/mart/widgets/mart_category_tile.dart';
import 'package:ride_sharing_user_app/features/mart/widgets/mart_product_card.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/helper/price_converter.dart';
import 'package:ride_sharing_user_app/util/app_colors.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// VitoMart storefront: scan-first home with a promo banner, icon category
/// rail, "Deals today" shelf, "Popular near you" shelf and the product grid,
/// plus a sticky live cart bar. All state lives on [MartController].
class MartStoreScreen extends StatefulWidget {
  const MartStoreScreen({super.key});

  @override
  State<MartStoreScreen> createState() => _MartStoreScreenState();
}

class _MartStoreScreenState extends State<MartStoreScreen> {
  final TextEditingController _searchController = TextEditingController();
  bool _isOffline = false;
  Timer? _searchDebounce;
  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;

  MartController get _martController => Get.find<MartController>();

  @override
  void initState() {
    super.initState();
    if (_martController.categories.isEmpty) {
      _martController.getCategories();
    }
    _martController.getProducts();
    _martController.getShelves();
    // Drive the offline banner (and disabled add-to-cart) from real
    // connectivity; refetch the catalog when the connection comes back.
    _connectivitySub = Connectivity().onConnectivityChanged.listen((results) {
      final offline = results.contains(ConnectivityResult.none);
      if (offline != _isOffline && mounted) {
        setState(() => _isOffline = offline);
        if (!offline) {
          _loadProducts();
        }
      }
    });
  }

  Future<void> _loadProducts() => _martController.getProducts();

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _connectivitySub?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 300), () {
      if (mounted) setState(() {});
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'vito_mart', showLogo: true),
      body: _isOffline ? _buildOfflineBody(context) : _buildBody(context),
      bottomNavigationBar: MartCartBar(onTap: _navigateToCart),
    );
  }

  Widget _buildOfflineBody(BuildContext context) {
    return Column(
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
          color: AppColors.offlineWarning,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.wifi_off, color: Colors.white, size: Dimensions.iconSizeMedium),
              const SizedBox(width: Dimensions.paddingSizeExtraSmall),
              Text('you_are_offline'.tr, style: textMedium.copyWith(color: Colors.white)),
            ],
          ),
        ),
        Expanded(child: _buildBody(context)),
      ],
    );
  }

  Widget _buildBody(BuildContext context) {
    return Column(
      children: [
        _buildSearchBar(context),
        _buildCategoryRail(context),
        Expanded(child: _buildAnimatedContent(context)),
      ],
    );
  }

  static const List<(String, String)> _sortOptions = [
    ('default', 'recommended'),
    ('price_asc', 'price_low_to_high'),
    ('price_desc', 'price_high_to_low'),
    ('popular', 'most_popular'),
  ];

  Widget _buildSearchBar(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
          Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault, Dimensions.paddingSizeSmall),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'search_products'.tr,
                prefixIcon: const Icon(Icons.search),
                suffixIcon: AnimatedOpacity(
                  opacity: _searchController.text.isEmpty ? 0.0 : 1.0,
                  duration: const Duration(milliseconds: 200),
                  child: IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: _searchController.text.isEmpty
                        ? null
                        : () {
                            _searchDebounce?.cancel();
                            _searchController.clear();
                            setState(() {});
                          },
                  ),
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: Dimensions.paddingSizeDefault,
                  vertical: Dimensions.paddingSizeSmall,
                ),
              ),
            ),
          ),
          const SizedBox(width: Dimensions.paddingSizeExtraSmall),
          IconButton(
            tooltip: 'favorites'.tr,
            onPressed: () => Get.to(() => const MartFavoritesScreen()),
            icon: Icon(Icons.favorite_border, color: Theme.of(context).primaryColor),
          ),
          IconButton(
            tooltip: 'mart_order_history'.tr,
            onPressed: () => Get.to(() => const MartOrderHistoryScreen()),
            icon: Icon(Icons.receipt_long, color: Theme.of(context).primaryColor),
          ),
        ],
      ),
    );
  }

  /// Horizontal icon-tile rail: "all" first, then the live categories, with a
  /// trailing "See all" tile opening the full categories grid.
  Widget _buildCategoryRail(BuildContext context) {
    return GetBuilder<MartController>(
      builder: (controller) {
        final categories = controller.categories
            .where((c) => c.name != null && c.name!.isNotEmpty)
            .toList();
        return SizedBox(
          height: 96,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall),
            itemCount: categories.length + 2,
            itemBuilder: (context, index) {
              if (index == 0) {
                return MartCategoryTile(
                  name: 'all',
                  selected: controller.selectedCategory == 'all',
                  iconSeed: 0,
                  onTap: () {
                    HapticFeedback.selectionClick();
                    controller.setCategory('all');
                  },
                );
              }
              if (index == categories.length + 1) {
                return _seeAllTile(context);
              }
              final category = categories[index - 1];
              return MartCategoryTile(
                name: category.name!,
                image: category.image,
                selected: controller.selectedCategory == category.name,
                iconSeed: index,
                onTap: () {
                  HapticFeedback.selectionClick();
                  controller.setCategory(category.name!);
                },
              );
            },
          ),
        );
      },
    );
  }

  Widget _seeAllTile(BuildContext context) {
    final primary = Theme.of(context).primaryColor;
    return InkWell(
      onTap: () => Get.to(() => const MartCategoriesScreen()),
      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
      child: SizedBox(
        width: 78,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: primary.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(Dimensions.radiusDefault + 4),
                border: Border.all(color: primary.withValues(alpha: 0.3)),
              ),
              child: Icon(Icons.grid_view_rounded, color: primary, size: 26),
            ),
            const SizedBox(height: 5),
            Text('see_all'.tr,
                style: textMedium.copyWith(
                    fontSize: Dimensions.fontSizeExtraSmall, color: primary),
                maxLines: 1,
                overflow: TextOverflow.ellipsis),
          ],
        ),
      ),
    );
  }

  Widget _buildAnimatedContent(BuildContext context) {
    return GetBuilder<MartController>(
      builder: (controller) {
        final query = _searchController.text.trim().toLowerCase();
        final selectedCategory = controller.selectedCategory;

        var filtered = controller.products;
        if (selectedCategory != 'all') {
          filtered = filtered.where((p) => p.category == selectedCategory).toList();
        }
        if (query.isNotEmpty) {
          filtered = filtered
              .where((p) => (p.name ?? '').toLowerCase().contains(query))
              .toList();
        }

        // The banner + Deals + Popular shelves only make sense on the
        // unfiltered default view.
        final defaultView =
            selectedCategory == 'all' && query.isEmpty && controller.selectedSort == 'default';

        final stateKey = '${selectedCategory}_${query}_${controller.selectedSort}';
        return AnimatedSwitcher(
          duration: const Duration(milliseconds: 200),
          transitionBuilder: (child, animation) =>
              FadeTransition(opacity: animation, child: child),
          child: controller.isLoading
              ? _buildShimmerGrid(context)
              : (filtered.isEmpty && !defaultView)
                  ? _buildEmptyState(context, key: ValueKey('empty_$stateKey'))
                  : _buildStorefront(context, controller, filtered,
                      showShelves: defaultView, key: ValueKey('grid_$stateKey')),
        );
      },
    );
  }

  Widget _buildShimmerGrid(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return GridView.builder(
      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.75,
        crossAxisSpacing: Dimensions.paddingSizeSmall,
        mainAxisSpacing: Dimensions.paddingSizeSmall,
      ),
      itemCount: 6,
      itemBuilder: (ctx, index) => Shimmer.fromColors(
        baseColor: isDark ? AppColors.shimmerBaseDark : AppColors.shimmerBaseLight,
        highlightColor:
            isDark ? AppColors.shimmerHighlightDark : AppColors.shimmerHighlightLight,
        child: Container(
          decoration: BoxDecoration(
            color: isDark ? AppColors.shimmerBaseDark : AppColors.shimmerBaseLight,
            borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(BuildContext context, {Key? key}) {
    return Center(
      key: key,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.store_outlined,
            size: 80,
            color: Theme.of(context).hintColor.withValues(alpha: 0.3),
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          Text(
            'no_products_available'.tr,
            style: textMedium.copyWith(
              fontSize: Dimensions.fontSizeLarge,
              color: Theme.of(context).hintColor,
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall),
          Text(
            'check_back_later'.tr,
            style: textRegular.copyWith(
              fontSize: Dimensions.fontSizeSmall,
              color: Theme.of(context).hintColor,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStorefront(BuildContext context, MartController controller,
      List<MartProductModel> filtered,
      {required bool showShelves, Key? key}) {
    final deals = controller.products.where((p) => p.onSale).take(10).toList();
    final popular = controller.popularProducts.isNotEmpty
        ? controller.popularProducts
        : controller.featuredProducts;

    return RefreshIndicator(
      onRefresh: _loadProducts,
      color: Theme.of(context).primaryColor,
      child: CustomScrollView(
        key: key,
        slivers: [
          if (showShelves) ...[
            SliverToBoxAdapter(child: _buildPromoBanner(context, controller)),
            if (deals.isNotEmpty) _buildShelf(context, 'deals_today', deals),
            if (popular.isNotEmpty) _buildShelf(context, 'popular_near_you', popular),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
                    Dimensions.paddingSizeSmall, Dimensions.paddingSizeDefault, 0),
                child: Text('all_products'.tr,
                    style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
              ),
            ),
          ],
          SliverToBoxAdapter(child: _buildSortChips(context, controller)),
          SliverPadding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            sliver: filtered.isEmpty
                ? SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
                      child: Text(
                        'no_products_available'.tr,
                        style: textRegular.copyWith(color: Theme.of(context).hintColor),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  )
                : SliverGrid(
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 0.75,
                      crossAxisSpacing: Dimensions.paddingSizeSmall,
                      mainAxisSpacing: Dimensions.paddingSizeSmall,
                    ),
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => MartProductCard(
                        product: filtered[index],
                        isOffline: _isOffline,
                        onAdd: _addToCart,
                      ),
                      childCount: filtered.length,
                    ),
                  ),
          ),
          // Keep the last row clear of the sticky cart bar.
          const SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeLarge)),
        ],
      ),
    );
  }

  /// Free-delivery / delivery-fee promo banner at the top of the default view.
  Widget _buildPromoBanner(BuildContext context, MartController controller) {
    final primary = Theme.of(context).primaryColor;
    final onPrimary = Theme.of(context).colorScheme.onPrimary;
    final fee = controller.deliveryFee;
    return Container(
      margin: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
          Dimensions.paddingSizeSmall, Dimensions.paddingSizeDefault, Dimensions.paddingSizeExtraSmall),
      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [primary, primary.withValues(alpha: 0.8)],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(Dimensions.radiusLarge),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  fee <= 0
                      ? 'free_delivery_today'.tr
                      : '${'delivery_fee'.tr}: ${PriceConverter.convertPrice(fee)}',
                  style: textBold.copyWith(
                      color: onPrimary, fontSize: Dimensions.fontSizeLarge),
                ),
                const SizedBox(height: 3),
                Text(
                  'mart_banner_subtitle'.tr,
                  style: textRegular.copyWith(
                      color: onPrimary.withValues(alpha: 0.9),
                      fontSize: Dimensions.fontSizeSmall),
                ),
              ],
            ),
          ),
          Icon(Icons.delivery_dining, color: onPrimary.withValues(alpha: 0.85), size: 44),
        ],
      ),
    );
  }

  Widget _buildSortChips(BuildContext context, MartController controller) {
    return SizedBox(
      height: 44,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
        itemCount: _sortOptions.length,
        itemBuilder: (context, index) {
          final (value, labelKey) = _sortOptions[index];
          return Padding(
            padding: const EdgeInsets.only(
                right: Dimensions.paddingSizeSmall, top: Dimensions.paddingSizeExtraSmall),
            child: ChoiceChip(
              label: Text(labelKey.tr,
                  style: textRegular.copyWith(fontSize: Dimensions.fontSizeSmall)),
              selected: controller.selectedSort == value,
              onSelected: (_) {
                HapticFeedback.selectionClick();
                controller.setSort(value);
              },
              selectedColor: Theme.of(context).primaryColor.withValues(alpha: 0.2),
            ),
          );
        },
      ),
    );
  }

  /// A horizontal product shelf (Deals today / Popular near you).
  Widget _buildShelf(BuildContext context, String titleKey, List<MartProductModel> items) {
    return SliverToBoxAdapter(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
                Dimensions.paddingSizeSmall, Dimensions.paddingSizeDefault,
                Dimensions.paddingSizeExtraSmall),
            child: Text(titleKey.tr,
                style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
          ),
          SizedBox(
            height: 230,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
              itemCount: items.length,
              itemBuilder: (context, index) => SizedBox(
                width: 160,
                child: Padding(
                  padding: const EdgeInsets.only(right: Dimensions.paddingSizeSmall),
                  child: MartProductCard(
                    product: items[index],
                    isOffline: _isOffline,
                    onAdd: _addToCart,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _addToCart(MartProductModel product) {
    _martController.addToCart({
      'id': product.id,
      'name': product.name,
      'price': product.effectivePrice,
      'image': product.image,
    });
    showCustomSnackBar('item_added_to_cart'.tr, isError: false);
  }

  void _navigateToCart() {
    Get.to(() => const MartCartScreen());
  }
}
