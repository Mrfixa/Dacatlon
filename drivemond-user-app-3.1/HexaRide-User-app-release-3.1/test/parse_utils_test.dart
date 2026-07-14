// Tests for parse_utils.dart - safe numeric coercion utilities

import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/util/parse_utils.dart';

void main() {
  group('toDoubleOr', () {
    test('returns double for num input', () {
      expect(toDoubleOr(42), 42.0);
      expect(toDoubleOr(3.14), 3.14);
      expect(toDoubleOr(-100), -100.0);
    });

    test('returns fallback for null input', () {
      expect(toDoubleOr(null), 0.0);
      expect(toDoubleOr(null, 99.0), 99.0);
    });

    test('returns fallback for non-numeric string', () {
      expect(toDoubleOr('abc'), 0.0);
      expect(toDoubleOr('abc', 5.0), 5.0);
    });

    test('parses numeric string', () {
      expect(toDoubleOr('42'), 42.0);
      expect(toDoubleOr('3.14'), 3.14);
      expect(toDoubleOr('-10.5'), -10.5);
    });

    test('handles integer as string', () {
      expect(toDoubleOr('100'), 100.0);
    });
  });

  group('toIntOr', () {
    test('returns int for num input', () {
      expect(toIntOr(42), 42);
      expect(toIntOr(3.7), 3);
      expect(toIntOr(-100), -100);
    });

    test('returns fallback for null input', () {
      expect(toIntOr(null), 0);
      expect(toIntOr(null, 99), 99);
    });

    test('returns fallback for non-numeric string', () {
      expect(toIntOr('abc'), 0);
      expect(toIntOr('abc', 5), 5);
    });

    test('parses numeric string', () {
      expect(toIntOr('42'), 42);
      expect(toIntOr('-10'), -10);
    });

    test('parses double string and truncates', () {
      expect(toIntOr('3.7'), 3);
      expect(toIntOr('10.9'), 10);
    });

    test('handles string with trailing whitespace', () {
      expect(toIntOr('  42  '), 42);
    });
  });

  group('toIntOrNull', () {
    test('returns int for num input', () {
      expect(toIntOrNull(42), 42);
      expect(toIntOrNull(3.7), 3);
    });

    test('returns null for null input', () {
      expect(toIntOrNull(null), isNull);
    });

    test('returns null for non-numeric string', () {
      expect(toIntOrNull('abc'), isNull);
    });

    test('parses numeric string', () {
      expect(toIntOrNull('42'), 42);
      expect(toIntOrNull('-10'), -10);
    });

    test('parses double string and truncates', () {
      expect(toIntOrNull('3.7'), 3);
    });
  });
}
