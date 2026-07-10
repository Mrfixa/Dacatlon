import 'package:flutter/material.dart';
import 'package:flutter_typeahead/flutter_typeahead.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// A searchable dropdown built on [TypeAheadField]: the user types to filter the
/// list to entries whose label contains the typed text, and picks one from the
/// suggestion overlay. Used for the vehicle brand / model / category selectors so
/// long lists become type-to-search instead of scroll-only. Tapping the field
/// with an empty query shows the full list, matching the old dropdown behaviour.
///
/// Typing an exact item name and leaving the field (submit or tap outside)
/// commits that item as if it had been tapped — otherwise the field would
/// visibly read e.g. "Toyota" while the form still holds the placeholder and
/// validation fails with a contradictory "select vehicle brand" message.
class SearchableDropdownField<T> extends StatefulWidget {
  final List<T> items;
  final TextEditingController controller;
  final String hintText;
  final String Function(T) itemLabel;
  final void Function(T) onSelected;

  const SearchableDropdownField({
    super.key,
    required this.items,
    required this.controller,
    required this.hintText,
    required this.itemLabel,
    required this.onSelected,
  });

  /// Pure, testable filter used by [suggestionsCallback]: returns the items whose
  /// label contains [pattern] (case-insensitive, trimmed); an empty pattern returns
  /// all items so tapping the field shows the full list like a dropdown.
  static List<T> filterSuggestions<T>(List<T> items, String pattern, String Function(T) itemLabel) {
    final query = pattern.trim().toLowerCase();
    if (query.isEmpty) return items;
    return items.where((item) => itemLabel(item).toLowerCase().contains(query)).toList();
  }

  /// Pure, testable exact-match lookup used when the user types a value without
  /// tapping the suggestion row: matches the raw or translated label,
  /// case-insensitively and trimmed. Returns null when nothing matches exactly.
  static T? findExactMatch<T>(List<T> items, String text, String Function(T) itemLabel) {
    final query = text.trim().toLowerCase();
    if (query.isEmpty) return null;
    for (final item in items) {
      final label = itemLabel(item);
      if (label.toLowerCase() == query || label.tr.toLowerCase() == query) return item;
    }
    return null;
  }

  @override
  State<SearchableDropdownField<T>> createState() => _SearchableDropdownFieldState<T>();
}

class _SearchableDropdownFieldState<T> extends State<SearchableDropdownField<T>> {
  // Last text committed through onSelected — used to skip redundant re-commits
  // (re-selecting the same brand would otherwise reset the chosen model).
  String? _lastCommittedText;

  void _commitExactMatchIfTyped() {
    final text = widget.controller.text;
    if (text.trim().isEmpty || text == _lastCommittedText) return;
    final match = SearchableDropdownField.findExactMatch(widget.items, text, widget.itemLabel);
    if (match != null) {
      widget.controller.text = widget.itemLabel(match).tr;
      _lastCommittedText = widget.controller.text;
      widget.onSelected(match);
    }
  }

  @override
  Widget build(BuildContext context) {
    return TypeAheadField<T>(
      controller: widget.controller,
      suggestionsCallback: (pattern) =>
          SearchableDropdownField.filterSuggestions(widget.items, pattern, widget.itemLabel),
      builder: (context, fieldController, focusNode) {
        return Container(
          width: Get.width,
          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            border: Border.all(width: .5, color: Theme.of(context).hintColor.withValues(alpha: .7)),
            borderRadius: BorderRadius.circular(Dimensions.paddingSizeOverLarge),
          ),
          child: TextField(
            controller: fieldController,
            focusNode: focusNode,
            cursorColor: Theme.of(context).primaryColor,
            style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyMedium!.color),
            onSubmitted: (_) => _commitExactMatchIfTyped(),
            onTapOutside: (_) {
              _commitExactMatchIfTyped();
              focusNode.unfocus();
            },
            decoration: InputDecoration(
              border: InputBorder.none,
              hintText: widget.hintText,
              hintStyle: textRegular.copyWith(color: Theme.of(context).hintColor),
              suffixIcon: Icon(Icons.keyboard_arrow_down, color: Theme.of(context).hintColor),
            ),
          ),
        );
      },
      itemBuilder: (context, item) {
        return Padding(
          padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
          child: Text(
            widget.itemLabel(item).tr,
            style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyMedium!.color),
          ),
        );
      },
      onSelected: (item) {
        widget.controller.text = widget.itemLabel(item).tr;
        _lastCommittedText = widget.controller.text;
        widget.onSelected(item);
      },
      emptyBuilder: (context) => Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        child: Text(
          'no_match_found'.tr,
          style: textRegular.copyWith(color: Theme.of(context).hintColor),
        ),
      ),
    );
  }
}
