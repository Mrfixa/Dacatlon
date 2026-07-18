import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/features/mart/controllers/mart_controller.dart';
import 'package:ride_sharing_user_app/features/mart/widgets/mart_category_tile.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// All-categories browse grid. Tapping a category applies it as the store
/// filter (via [MartController.setCategory]) and pops back to the storefront.
class MartCategoriesScreen extends StatefulWidget {
  const MartCategoriesScreen({super.key});

  @override
  State<MartCategoriesScreen> createState() => _MartCategoriesScreenState();
}

class _MartCategoriesScreenState extends State<MartCategoriesScreen> {
  @override
  void initState() {
    super.initState();
    final controller = Get.find<MartController>();
    if (controller.categories.isEmpty) {
      controller.getCategories();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'all_categories'.tr),
      body: GetBuilder<MartController>(
        builder: (controller) {
          if (controller.categories.isEmpty && controller.categoriesLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (controller.categories.isEmpty && controller.categoriesFailed) {
            // A failed fetch is not "no categories" — offer a retry.
            return Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Text('something_went_wrong'.tr,
                    style: textRegular.copyWith(color: Theme.of(context).hintColor)),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                TextButton(
                  onPressed: () => controller.getCategories(),
                  child: Text('retry'.tr),
                ),
              ]),
            );
          }
          if (controller.categories.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
                child: Text(
                  'no_data_found'.tr,
                  style: textRegular.copyWith(color: Theme.of(context).hintColor),
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }
          // Leading "all" pseudo-category clears the filter.
          final entries = <({String name, String? image})>[
            (name: 'all', image: null),
            ...controller.categories
                .where((c) => c.name != null && c.name!.isNotEmpty)
                .map((c) => (name: c.name!, image: c.image)),
          ];
          return GridView.builder(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              childAspectRatio: 0.82,
              crossAxisSpacing: Dimensions.paddingSizeSmall,
              mainAxisSpacing: Dimensions.paddingSizeDefault,
            ),
            itemCount: entries.length,
            itemBuilder: (context, index) {
              final entry = entries[index];
              return MartCategoryTile(
                name: entry.name,
                image: entry.image,
                selected: controller.selectedCategory == entry.name,
                iconSeed: index,
                size: 76,
                onTap: () {
                  HapticFeedback.selectionClick();
                  controller.setCategory(entry.name);
                  Get.back();
                },
              );
            },
          );
        },
      ),
    );
  }
}
