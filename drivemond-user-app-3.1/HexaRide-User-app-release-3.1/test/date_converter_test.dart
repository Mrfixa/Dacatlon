// Tests for date_converter.dart - date formatting utilities

import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/helper/date_converter.dart';

void main() {
  group('DateConverter', () {
    final testDate = DateTime(2024, 6, 15, 14, 30, 45);

    group('formatDate', () {
      test('formats date in standard format', () {
        final result = DateConverter.formatDate(testDate);
        expect(result, contains('2024-06-15'));
        expect(result, contains('02:30:45 PM'));
      });
    });

    group('dateToTimeOnly', () {
      test('formats time only', () {
        final result = DateConverter.dateToTimeOnly(testDate);
        expect(result, contains('02:30'));
        expect(result, contains('PM'));
      });
    });

    group('dateToDateAndTime', () {
      test('formats date and time', () {
        final result = DateConverter.dateToDateAndTime(testDate);
        expect(result, equals('2024-06-15 14:30'));
      });
    });

    group('dateTimeStringToDateOnly', () {
      test('parses and formats date only', () {
        const input = '2024-06-15 14:30:45';
        final result = DateConverter.dateTimeStringToDateOnly(input);
        expect(result, equals('15 Jun 2024'));
      });
    });

    group('dateTimeStringToDate', () {
      test('parses datetime string to DateTime', () {
        const input = '2024-06-15 14:30:45';
        final result = DateConverter.dateTimeStringToDate(input);
        expect(result.year, 2024);
        expect(result.month, 6);
        expect(result.day, 15);
        expect(result.hour, 14);
        expect(result.minute, 30);
        expect(result.second, 45);
      });
    });

    group('isoStringToLocalDate', () {
      test('parses ISO string with milliseconds', () {
        const input = '2024-06-15T14:30:45.123';
        final result = DateConverter.isoStringToLocalDate(input);
        expect(result.year, 2024);
        expect(result.month, 6);
        expect(result.day, 15);
      });
    });

    group('isoStringToLocalString', () {
      test('converts ISO string to formatted local string', () {
        const input = '2024-06-15T14:30:00';
        final result = DateConverter.isoStringToLocalString(input);
        expect(result, contains('2024-06-15'));
        expect(result, contains('14:30'));
      });
    });

    group('isoStringToLocalDateOnly', () {
      test('formats ISO date to short format', () {
        const input = '2024-06-15T14:30:00';
        final result = DateConverter.isoStringToLocalDateOnly(input);
        expect(result, equals('15-06-24'));
      });
    });

    group('convertTimeToTime', () {
      test('converts 24h time to 12h format', () {
        const input = '14:30';
        final result = DateConverter.convertTimeToTime(input);
        expect(result, contains('02:30'));
        expect(result, contains('PM'));
      });

      test('converts morning time', () {
        const input = '09:15';
        final result = DateConverter.convertTimeToTime(input);
        expect(result, contains('09:15'));
        expect(result, contains('AM'));
      });
    });

    group('convertStringTimeToDate', () {
      test('parses time string to DateTime', () {
        const input = '14:30';
        final result = DateConverter.convertStringTimeToDate(input);
        expect(result.hour, 14);
        expect(result.minute, 30);
      });
    });

    group('localDateToIsoString', () {
      test('converts DateTime to ISO string', () {
        final result = DateConverter.localDateToIsoString(testDate);
        expect(result, startsWith('2024-06-15T'));
        expect(result, contains('14:30:45'));
      });
    });

    group('localToIsoString', () {
      test('formats DateTime with month name', () {
        final result = DateConverter.localToIsoString(testDate);
        expect(result, contains('June'));
        expect(result, contains('02:30'));
      });
    });

    group('stringToLocalDateOnly', () {
      test('converts date string to formatted date', () {
        const input = '2024-06-15';
        final result = DateConverter.stringToLocalDateOnly(input);
        expect(result, equals('15 Jun 2024'));
      });
    });

    group('stringToLocalDateTime', () {
      test('converts datetime string to formatted string', () {
        const input = '2024-06-15 14:30';
        final result = DateConverter.stringToLocalDateTime(input);
        expect(result, contains('15/06/2024'));
        expect(result, contains('02:30'));
      });
    });

    group('stringDateTimeToTimeOnly', () {
      test('extracts time from datetime string', () {
        const input = '2024-06-15 14:30';
        final result = DateConverter.stringDateTimeToTimeOnly(input);
        expect(result, contains('02:30'));
        expect(result, contains('PM'));
      });
    });

    group('tripDetailsShowFormat', () {
      test('formats datetime for trip details', () {
        const input = '2024-06-15 14:30:00';
        final result = DateConverter.tripDetailsShowFormat(input);
        expect(result, contains('15 Jun 2024'));
      });
    });
  });
}
