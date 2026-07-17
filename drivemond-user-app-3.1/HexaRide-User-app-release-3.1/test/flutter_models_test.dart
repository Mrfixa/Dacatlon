// Comprehensive Flutter Model Tests

import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/features/address/domain/models/address_model.dart';
import 'package:ride_sharing_user_app/features/coupon/domain/models/coupon_model.dart';
import 'package:ride_sharing_user_app/features/home/domain/models/banner_model.dart';
import 'package:ride_sharing_user_app/features/home/domain/models/categoty_model.dart';
import 'package:ride_sharing_user_app/features/location/domain/models/last_location_model.dart';
import 'package:ride_sharing_user_app/features/location/domain/models/place_details_model.dart';
import 'package:ride_sharing_user_app/features/location/domain/models/prediction_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_category_model.dart';
import 'package:ride_sharing_user_app/features/message/domain/models/channel_model.dart';
import 'package:ride_sharing_user_app/features/message/domain/models/message_model.dart';
import 'package:ride_sharing_user_app/features/my_level/domain/model/level_model.dart';
import 'package:ride_sharing_user_app/features/notification/domain/models/notification_model.dart';
import 'package:ride_sharing_user_app/features/my_offer/domain/models/best_offer_model.dart';
import 'package:ride_sharing_user_app/features/onboard/domain/models/on_boarding_model.dart';
import 'package:ride_sharing_user_app/features/parcel/domain/models/parcel_category_model.dart';
import 'package:ride_sharing_user_app/features/profile/domain/models/profile_model.dart';
import 'package:ride_sharing_user_app/features/ride/domain/models/estimated_fare_model.dart';
import 'package:ride_sharing_user_app/features/ride/domain/models/nearest_driver_model.dart';
import 'package:ride_sharing_user_app/features/ride/domain/models/ride_list_model.dart';
import 'package:ride_sharing_user_app/features/ride/domain/models/trip_details_model.dart';
import 'package:ride_sharing_user_app/features/splash/domain/models/config_model.dart';
import 'package:ride_sharing_user_app/features/wallet/domain/models/transaction_model.dart';

void main() {
  group('AddressModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': 1,
            'user_id': 'user123',
            'address': '123 Main St',
            'latitude': '40.7128',
            'longitude': '-74.0060',
          }
        ],
      };

      final address = AddressModel.fromJson(json);

      expect(address.responseCode, '200');
      expect(address.data, isNotNull);
      expect(address.data!.length, 1);
      expect(address.data![0].address, '123 Main St');
      expect(address.data![0].latitude, 40.7128);
    });

    test('Address parses correctly', () {
      final json = {
        'id': 1,
        'address': '123 Main St',
        'latitude': '40.7128',
        'longitude': '-74.0060',
      };

      final address = Address.fromJson(json);

      expect(address.id, 1);
      expect(address.address, '123 Main St');
      expect(address.latitude, 40.7128);
    });
  });

  group('CouponModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': '1',
            'name': 'Test Coupon',
            'coupon': 'DISCOUNT10',
            'min_trip_amount': '50.00',
            'max_coupon_amount': '20.00',
            'zone_coupon': <String>[],
            'customer_level_coupon': <String>[],
            'customer_coupon': <String>[],
            'category_coupon': <String>[],
          }
        ],
      };

      final coupon = CouponModel.fromJson(json);

      expect(coupon.responseCode, '200');
      expect(coupon.data, isNotNull);
      expect(coupon.data!.length, 1);
      expect(coupon.data![0].name, 'Test Coupon');
      expect(coupon.data![0].coupon, 'DISCOUNT10');
    });

    test('Coupon parses correctly', () {
      final json = {
        'id': '1',
        'name': 'Test Coupon',
        'coupon': 'DISCOUNT10',
        'zone_coupon': <String>[],
        'customer_level_coupon': <String>[],
        'customer_coupon': <String>[],
        'category_coupon': <String>[],
      };

      final coupon = Coupon.fromJson(json);

      expect(coupon.id, '1');
      expect(coupon.name, 'Test Coupon');
    });
  });

  group('BannerModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'total_size': 10,
        'data': [
          {
            'id': '1',
            'name': 'Test Banner',
            'description': 'Test description',
            'image': 'banner.jpg',
          }
        ],
      };

      final banner = BannerModel.fromJson(json);

      expect(banner.totalSize, 10);
      expect(banner.data, isNotNull);
      expect(banner.data!.length, 1);
      expect(banner.data![0].name, 'Test Banner');
      expect(banner.data![0].image, 'banner.jpg');
    });
  });

  group('CategoryModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': '1',
            'name': 'Food',
            'image': 'food.jpg',
          }
        ],
      };

      final category = CategoryModel.fromJson(json);

      expect(category.responseCode, '200');
      expect(category.data, isNotNull);
      expect(category.data!.length, 1);
      expect(category.data![0].name, 'Food');
    });
  });

  group('LastLocationModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'id': 1,
        'user_id': 'user123',
        'latitude': '40.7128',
        'longitude': '-74.0060',
      };

      final location = LastLocationModel.fromJson(json);

      expect(location.id, 1);
      expect(location.userId, 'user123');
      expect(location.latitude, '40.7128');
      expect(location.longitude, '-74.0060');
    });
  });

  group('PlaceDetailsModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': {
          'id': 'place123',
          'name': 'Test Place',
          'formattedAddress': '123 Main St',
          'types': <String>['restaurant'],
          'location': {
            'latitude': 40.7128,
            'longitude': -74.0060,
          },
        },
        'errors': <String>[],
      };

      final place = PlaceDetailsModel.fromJson(json);

      expect(place.responseCode, '200');
      expect(place.data, isNotNull);
      expect(place.data!.name, 'Test Place');
      expect(place.data!.location!.latitude, 40.7128);
    });
  });

  group('PredictionModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': {
          'suggestions': [
            {
              'placePrediction': {
                'place': 'Test Place',
                'placeId': 'place123',
                'text': {'text': 'Test Description', 'matches': <dynamic>[]},
                'types': <String>['restaurant'],
              }
            }
          ],
        },
        'errors': <String>[],
      };

      final prediction = PredictionModel.fromJson(json);

      expect(prediction.responseCode, '200');
      expect(prediction.data, isNotNull);
      expect(prediction.data!.suggestions, isNotNull);
      expect(prediction.data!.suggestions!.length, 1);
      expect(prediction.data!.suggestions![0].placePrediction!.place, 'Test Place');
    });
  });

  group('MartCategoryModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'id': '1',
        'name': 'Drinks',
        'image': 'drinks.jpg',
        'slug': 'drinks',
      };

      final category = MartCategoryModel.fromJson(json);

      expect(category.id, '1');
      expect(category.name, 'Drinks');
      expect(category.slug, 'drinks');
    });

    test('fromJson handles null values', () {
      final json = <String, dynamic>{};

      final category = MartCategoryModel.fromJson(json);

      expect(category.id, isNull);
      expect(category.name, isNull);
    });
  });

  group('ChannelModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': '1',
            'trip_id': 'trip123',
          }
        ],
      };

      final channel = ChannelModel.fromJson(json);

      expect(channel.responseCode, '200');
      expect(channel.data, isNotNull);
      expect(channel.data!.length, 1);
      expect(channel.data![0].id, '1');
      expect(channel.data![0].tripId, 'trip123');
    });
  });

  group('MessageModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': 1,
            'message': 'Hello',
            'user_id': 'user123',
            'created_at': '2024-01-01T00:00:00',
          }
        ],
      };

      final message = MessageModel.fromJson(json);

      expect(message.responseCode, '200');
      expect(message.data, isNotNull);
      expect(message.data!.length, 1);
      expect(message.data![0].message, 'Hello');
      expect(message.data![0].userId, 'user123');
    });
  });

  group('LevelModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': {
          'current_level': {
            'id': '1',
            'sequence': 1,
            'name': 'Gold',
            'reward_type': 'points',
            'reward_amount': '100',
            'targeted_ride': '10',
            'targeted_ride_point': '5',
            'targeted_amount': '1000',
            'targeted_amount_point': '10',
            'targeted_cancel': '2',
            'targeted_cancel_point': '1',
            'targeted_review': '5',
            'targeted_review_point': '2',
          },
        },
        'errors': <String>[],
      };

      final level = LevelModel.fromJson(json);

      expect(level.responseCode, '200');
      expect(level.data, isNotNull);
      expect(level.data!.currentLevel, isNotNull);
      expect(level.data!.currentLevel!.name, 'Gold');
    });
  });

  group('BestOfferModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': '1',
            'title': 'Discount Offer',
            'discount_amount': 10,
            'discount_amount_type': 'percent',
            'zone_discount': <String>[],
            'customer_level_discount': <String>[],
            'customer_discount': <String>[],
            'module_discount': <String>[],
          }
        ],
      };

      final offer = BestOfferModel.fromJson(json);

      expect(offer.responseCode, '200');
      expect(offer.data, isNotNull);
      expect(offer.data!.length, 1);
      expect(offer.data![0].title, 'Discount Offer');
      expect(offer.data![0].discountAmount, 10);
    });
  });

  group('NotificationModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': 1,
            'title': 'Test Notification',
            'description': 'Test description',
            'is_read': false,
            'created_at': '2024-01-01T00:00:00',
          }
        ],
      };

      final notification = NotificationsModel.fromJson(json);

      expect(notification.responseCode, '200');
      expect(notification.data, isNotNull);
      expect(notification.data!.length, 1);
      expect(notification.data![0].title, 'Test Notification');
      expect(notification.data![0].isRead, false);
    });
  });

  group('OnBoardingModel', () {
    test('constructor works correctly', () {
      final onboarding = OnBoardingModel(
        title1: 'Welcome',
        title2: 'Subtitle 2',
        title3: 'Subtitle 3',
        title4: 'Subtitle 4',
        image: 'onboarding.jpg',
      );

      expect(onboarding.title1, 'Welcome');
      expect(onboarding.image, 'onboarding.jpg');
    });
  });

  group('ParcelCategoryModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'data': [
          {
            'id': '1',
            'name': 'Documents',
            'image': 'documents.jpg',
            'is_active': true,
          }
        ],
      };

      final category = ParcelCategoryModel.fromJson(json);

      expect(category.responseCode, '200');
      expect(category.data, isNotNull);
      expect(category.data!.length, 1);
      expect(category.data![0].name, 'Documents');
      expect(category.data![0].isActive, 1);
    });
  });

  group('ProfileModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'data': {
          'id': '1',
          'first_name': 'John',
          'last_name': 'Doe',
          'email': 'john@example.com',
          'phone': '+1234567890',
          'is_active': 1,
          'user_rating': '4.5',
          'total_ride_count': '10',
          'completion_percent': 95.0,
        },
      };

      final profile = ProfileModel.fromJson(json);

      expect(profile.data, isNotNull);
      expect(profile.data!.firstName, 'John');
      expect(profile.data!.lastName, 'Doe');
      expect(profile.data!.email, 'john@example.com');
    });
  });

  group('EstimatedFareModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'message': 'Success',
        'data': [
          {
            'id': '1',
            'base_fare': 5.0,
            'base_fare_per_km': 2.0,
            'fare': 50.0,
            'estimated_distance': '10.5',
            'estimated_fare': 50.0,
            'discount_amount': 0.0,
            'discount_fare': 50.0,
          }
        ],
      };

      final fare = EstimatedFareModel.fromJson(json);

      expect(fare.responseCode, '200');
      expect(fare.data, isNotNull);
      expect(fare.data!.length, 1);
      expect(fare.data![0].baseFare, 5.0);
      expect(fare.data![0].estimatedDistance, '10.5');
    });
  });

  group('NearestDriverModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'data': [
          {
            'latitude': '40.7128',
            'longitude': '-74.0060',
            'category': 'economy',
          }
        ],
      };

      final driver = NearestDriverModel.fromJson(json);

      expect(driver.data, isNotNull);
      expect(driver.data!.length, 1);
      expect(driver.data![0].latitude, '40.7128');
      expect(driver.data![0].category, 'economy');
    });
  });

  group('RideListModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'message': 'Success',
        'data': [
          {
            'id': '1',
            'ref_id': 'ride123',
            'current_status': 'completed',
            'estimated_fare': 50.0,
            'estimated_distance': 10.0,
            'actual_fare': 55.0,
            'discount_actual_fare': 55.0,
            'due_amount': 0.0,
            'vat_tax': 5.0,
            'tips': 0.0,
          }
        ],
      };

      final ride = RideListModel.fromJson(json);

      expect(ride.responseCode, '200');
      expect(ride.data, isNotNull);
      expect(ride.data!.length, 1);
      expect(ride.data![0].id, '1');
      expect(ride.data![0].currentStatus, 'completed');
    });
  });

  group('TripDetailsModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'data': {
          'id': '1',
          'ref_id': 'trip123',
          'current_status': 'pending',
          'pickup_address': '123 Main St',
          'destination_address': '456 Oak Ave',
          'estimated_fare': 50.0,
          'estimated_distance': 10.0,
          'actual_fare': 55.0,
          'discount_actual_fare': 55.0,
          'actual_time': '30 mins',
          'actual_distance': '11 km',
          'waiting_time': '2 mins',
          'idle_time': '1 min',
          'waiting_fare': '1.00',
          'due_amount': 0.0,
          'vat_tax': 5.0,
          'tips': 0.0,
          'total_fare': '60.00',
          'created_at': '2024-01-01',
        },
      };

      final trip = TripDetailsModel.fromJson(json);

      expect(trip.data, isNotNull);
      expect(trip.data!.id, '1');
      expect(trip.data!.refId, 'trip123');
      expect(trip.data!.currentStatus, 'pending');
      expect(trip.data!.pickupAddress, '123 Main St');
      expect(trip.data!.destinationAddress, '456 Oak Ave');
    });
  });

  group('ConfigModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'currency_symbol': '\$',
        'currency_symbol_position': 'left',
        'currency_decimal_point': '2',
        'business_name': 'Test Business',
        'app_minimum_version_for_android': 1.0,
        'app_url_for_android': 'https://play.google.com',
        'app_minimum_version_for_ios': 1.0,
        'app_url_for_ios': 'https://apps.apple.com',
        'websocket_scheme': 'wss',
        'parcel_weight_unit': 'kg',
        'zone_extra_fare': <dynamic>[],
      };

      final config = ConfigModel.fromJson(json);

      expect(config.currencySymbol, '\$');
      expect(config.currencySymbolPosition, 'left');
      expect(config.currencyDecimalPoint, '2');
      expect(config.businessName, 'Test Business');
      expect(config.androidAppMinimumVersion, 1.0);
    });
  });

  group('TransactionModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'response_code': '200',
        'message': 'Success',
        'data': [
          {
            'id': '1',
            'attribute': 'wallet',
            'attribute_id': '1',
            'debit': 50.0,
            'credit': 100.0,
            'created_at': '2024-01-01T00:00:00',
          }
        ],
      };

      final transaction = TransactionModel.fromJson(json);

      expect(transaction.responseCode, '200');
      expect(transaction.data, isNotNull);
      expect(transaction.data!.length, 1);
      expect(transaction.data![0].id, '1');
      expect(transaction.data![0].credit, 100.0);
    });

    test('Transaction parses correctly', () {
      final json = {
        'id': '1',
        'attribute': 'wallet',
        'attribute_id': '1',
        'debit': 50.0,
        'credit': 100.0,
        'created_at': '2024-01-01T00:00:00',
      };

      final transaction = Transaction.fromJson(json);

      expect(transaction.id, '1');
      expect(transaction.attribute, 'wallet');
      expect(transaction.debit, 50.0);
      expect(transaction.credit, 100.0);
    });
  });
}
