// This is a basic Flutter widget test for the Vito app.

import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/features/mart/domain/mart_order_status.dart';

void main() {
  group('Mart Order Status helpers', () {
    test('kMartOrderSteps contains all order steps', () {
      expect(kMartOrderSteps, ['pending', 'accepted', 'picked_up', 'delivered']);
    });

    test('martOrderStepIndex returns correct indices', () {
      expect(martOrderStepIndex('pending'), 0);
      expect(martOrderStepIndex('accepted'), 1);
      expect(martOrderStepIndex('picked_up'), 2);
      expect(martOrderStepIndex('delivered'), 3);
    });

    test('martOrderStepIndex returns -1 for cancelled', () {
      expect(martOrderStepIndex('cancelled'), -1);
    });

    test('martOrderStepIndex returns 0 for unknown status', () {
      expect(martOrderStepIndex('unknown'), 0);
      expect(martOrderStepIndex(''), 0);
    });

    test('isMartOrderTerminal returns true for terminal states', () {
      expect(isMartOrderTerminal('delivered'), isTrue);
      expect(isMartOrderTerminal('cancelled'), isTrue);
    });

    test('isMartOrderTerminal returns false for non-terminal states', () {
      expect(isMartOrderTerminal('pending'), isFalse);
      expect(isMartOrderTerminal('accepted'), isFalse);
      expect(isMartOrderTerminal('picked_up'), isFalse);
    });

    test('canCancelMartOrder returns true for cancellable states', () {
      expect(canCancelMartOrder('pending'), isTrue);
      expect(canCancelMartOrder('accepted'), isTrue);
    });

    test('canCancelMartOrder returns false for non-cancellable states', () {
      expect(canCancelMartOrder('picked_up'), isFalse);
      expect(canCancelMartOrder('delivered'), isFalse);
      expect(canCancelMartOrder('cancelled'), isFalse);
    });
  });
}
