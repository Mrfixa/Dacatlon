// Tests for Mart domain models

import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_product_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_order_item_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_order_model.dart';

void main() {
  group('MartProductModel', () {
    test('fromJson parses basic fields', () {
      final json = {
        'id': '1',
        'name': 'Test Product',
        'price': '10.99',
        'is_active': true,
      };

      final product = MartProductModel.fromJson(json);

      expect(product.id, '1');
      expect(product.name, 'Test Product');
      expect(product.price, 10.99);
      expect(product.isActive, isTrue);
    });

    test('fromJson handles numeric price', () {
      final json = {
        'id': '1',
        'name': 'Test Product',
        'price': 10.99,
      };

      final product = MartProductModel.fromJson(json);
      expect(product.price, 10.99);
    });

    test('fromJson handles null values', () {
      final json = <String, dynamic>{};

      final product = MartProductModel.fromJson(json);

      expect(product.id, isNull);
      expect(product.name, isNull);
      expect(product.price, 0);
      expect(product.isActive, isFalse);
    });

    test('effectivePrice returns discount price when lower', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: 80,
      );

      expect(product.effectivePrice, 80);
    });

    test('effectivePrice returns original price when discount is higher', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: 120,
      );

      expect(product.effectivePrice, 100);
    });

    test('effectivePrice returns original price when no discount', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: null,
      );

      expect(product.effectivePrice, 100);
    });

    test('effectivePrice returns original price when discount is zero', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: 0,
      );

      expect(product.effectivePrice, 100);
    });

    test('onSale returns true when sale price applies', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: 80,
      );

      expect(product.onSale, isTrue);
    });

    test('onSale returns false when no discount', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: null,
      );

      expect(product.onSale, isFalse);
    });

    test('onSale returns false when discount equals price', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: 100,
      );

      expect(product.onSale, isFalse);
    });

    test('onSale returns false when discount is higher', () {
      final product = MartProductModel(
        price: 100,
        discountPrice: 120,
      );

      expect(product.onSale, isFalse);
    });

    test('inStock returns isActive value', () {
      final activeProduct = MartProductModel(isActive: true);
      final inactiveProduct = MartProductModel(isActive: false);

      expect(activeProduct.inStock, isTrue);
      expect(inactiveProduct.inStock, isFalse);
    });

    test('toJson serializes correctly', () {
      final product = MartProductModel(
        id: '1',
        name: 'Test Product',
        price: 10.99,
        discountPrice: 8.99,
        isActive: true,
        isFeatured: true,
        isPopular: false,
        soldCount: 100,
      );

      final json = product.toJson();

      expect(json['id'], '1');
      expect(json['name'], 'Test Product');
      expect(json['price'], 10.99);
      expect(json['discount_price'], 8.99);
      expect(json['is_active'], isTrue);
      expect(json['is_featured'], isTrue);
      expect(json['is_popular'], isFalse);
      expect(json['sold_count'], 100);
    });

    test('fromJson parses is_active as boolean or int', () {
      final boolJson = {'is_active': true};
      final intJson = {'is_active': 1};
      final stringJson = {'is_active': '1'};
      final falseJson = {'is_active': false};

      expect(MartProductModel.fromJson(boolJson).isActive, isTrue);
      expect(MartProductModel.fromJson(intJson).isActive, isTrue);
      expect(MartProductModel.fromJson(stringJson).isActive, isTrue);
      expect(MartProductModel.fromJson(falseJson).isActive, isFalse);
    });
  });

  group('MartOrderItemModel', () {
    test('fromJson parses basic fields', () {
      final json = {
        'id': '1',
        'product_id': 'prod-1',
        'quantity': '3',
        'unit_price': '10.00',
        'total_price': '30.00',
      };

      final item = MartOrderItemModel.fromJson(json);

      expect(item.id, '1');
      expect(item.productId, 'prod-1');
      expect(item.quantity, 3);
      expect(item.unitPrice, 10.00);
      expect(item.totalPrice, 30.00);
    });

    test('fromJson handles null values', () {
      final json = <String, dynamic>{};

      final item = MartOrderItemModel.fromJson(json);

      expect(item.id, isNull);
      expect(item.productId, isNull);
      expect(item.quantity, 0);
      expect(item.unitPrice, 0);
      expect(item.totalPrice, 0);
    });

    test('displayName returns product name when available', () {
      final product = MartProductModel(name: 'Test Product');
      final item = MartOrderItemModel(product: product);

      expect(item.displayName, 'Test Product');
    });

    test('displayName returns Item when no product', () {
      final item = MartOrderItemModel();

      expect(item.displayName, 'Item');
    });

    test('toJson serializes correctly', () {
      final item = MartOrderItemModel(
        id: '1',
        productId: 'prod-1',
        quantity: 3,
        unitPrice: 10.00,
        totalPrice: 30.00,
      );

      final json = item.toJson();

      expect(json['id'], '1');
      expect(json['product_id'], 'prod-1');
      expect(json['quantity'], 3);
      expect(json['unit_price'], 10.00);
      expect(json['total_price'], 30.00);
    });
  });

  group('MartOrderModel', () {
    test('fromJson parses basic fields', () {
      final json = {
        'id': '1',
        'ref_id': 'REF-123',
        'status': 'pending',
        'total_amount': '50.00',
        'tip_amount': '5.00',
        'discount_amount': '10.00',
        'payment_status': 'paid',
        'payment_method': 'wallet',
        'delivery_address': '123 Main St',
      };

      final order = MartOrderModel.fromJson(json);

      expect(order.id, '1');
      expect(order.refId, 'REF-123');
      expect(order.status, 'pending');
      expect(order.totalAmount, 50.00);
      expect(order.tipAmount, 5.00);
      expect(order.discountAmount, 10.00);
      expect(order.paymentStatus, 'paid');
      expect(order.paymentMethod, 'wallet');
      expect(order.deliveryAddress, '123 Main St');
    });

    test('fromJson parses driver name from nested object', () {
      final json = {
        'driver': {
          'first_name': 'John',
          'last_name': 'Doe',
        },
      };

      final order = MartOrderModel.fromJson(json);

      expect(order.driverName, 'John Doe');
    });

    test('fromJson handles missing driver name parts', () {
      final json = {
        'driver': {
          'first_name': 'John',
        },
      };

      final order = MartOrderModel.fromJson(json);
      expect(order.driverName, 'John');
    });

    test('fromJson parses items list', () {
      final json = {
        'items': [
          {
            'id': '1',
            'product_id': 'prod-1',
            'quantity': '2',
            'unit_price': '10.00',
            'total_price': '20.00',
          },
          {
            'id': '2',
            'product_id': 'prod-2',
            'quantity': '1',
            'unit_price': '15.00',
            'total_price': '15.00',
          },
        ],
      };

      final order = MartOrderModel.fromJson(json);

      expect(order.items.length, 2);
      expect(order.items[0].id, '1');
      expect(order.items[1].id, '2');
    });

    test('fromJson handles null items', () {
      final json = <String, dynamic>{};

      final order = MartOrderModel.fromJson(json);

      expect(order.items, isEmpty);
    });

    test('itemCount sums quantities', () {
      final order = MartOrderModel(
        items: [
          MartOrderItemModel(quantity: 2),
          MartOrderItemModel(quantity: 3),
          MartOrderItemModel(quantity: 1),
        ],
      );

      expect(order.itemCount, 6);
    });

    test('itemCount returns 0 for empty items', () {
      final order = MartOrderModel();

      expect(order.itemCount, 0);
    });

    test('toJson serializes correctly', () {
      final order = MartOrderModel(
        id: '1',
        refId: 'REF-123',
        status: 'pending',
        totalAmount: 50.00,
        items: [
          MartOrderItemModel(
            id: '1',
            productId: 'prod-1',
            quantity: 2,
            unitPrice: 10.00,
            totalPrice: 20.00,
          ),
        ],
      );

      final json = order.toJson();

      expect(json['id'], '1');
      expect(json['ref_id'], 'REF-123');
      expect(json['status'], 'pending');
      expect(json['total_amount'], 50.00);
      expect(json['items'], isA<List>());
      expect((json['items'] as List).length, 1);
    });

    test('fromJson parses driver location fields', () {
      final json = {
        'driver_lat': '40.7128',
        'driver_lng': '-74.0060',
        'estimated_arrival': '10 mins',
      };

      final order = MartOrderModel.fromJson(json);

      expect(order.driverLat, 40.7128);
      expect(order.driverLng, -74.0060);
      expect(order.estimatedArrival, '10 mins');
    });
  });
}
