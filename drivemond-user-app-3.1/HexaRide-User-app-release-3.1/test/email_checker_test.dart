// Tests for email_checker.dart - email validation utility

import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/helper/email_checker.dart';

void main() {
  group('EmailChecker.isNotValid', () {
    group('valid emails', () {
      test('accepts simple email', () {
        expect(EmailChecker.isNotValid('test@example.com'), isFalse);
      });

      test('accepts email with subdomain', () {
        expect(EmailChecker.isNotValid('user@mail.example.com'), isFalse);
      });

      test('accepts email with dots in local part', () {
        expect(EmailChecker.isNotValid('first.last@example.com'), isFalse);
      });

      test('accepts email with plus sign', () {
        expect(EmailChecker.isNotValid('user+tag@example.com'), isFalse);
      });

      test('accepts email with numbers', () {
        expect(EmailChecker.isNotValid('user123@example.com'), isFalse);
        expect(EmailChecker.isNotValid('123user@example.com'), isFalse);
      });

      test('accepts email with underscore', () {
        expect(EmailChecker.isNotValid('user_name@example.com'), isFalse);
      });

      test('accepts email with hyphen', () {
        expect(EmailChecker.isNotValid('user-name@example.com'), isFalse);
      });
    });

    group('invalid emails', () {
      test('rejects email without @', () {
        expect(EmailChecker.isNotValid('testexample.com'), isTrue);
      });

      test('rejects email without domain', () {
        expect(EmailChecker.isNotValid('test@'), isTrue);
      });

      test('rejects email without local part', () {
        expect(EmailChecker.isNotValid('@example.com'), isTrue);
      });

      test('rejects email without TLD', () {
        expect(EmailChecker.isNotValid('test@example'), isTrue);
      });

      test('rejects email with space', () {
        expect(EmailChecker.isNotValid('test @example.com'), isTrue);
        expect(EmailChecker.isNotValid('test@ example.com'), isTrue);
      });

      test('rejects empty string', () {
        expect(EmailChecker.isNotValid(''), isTrue);
      });
    });
  });
}
