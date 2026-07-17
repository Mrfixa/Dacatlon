<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartOrderItem;
use PHPUnit\Framework\TestCase;

class MartOrderItemTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $item = new MartOrderItem([
            'order_id' => 'order-uuid-123',
            'product_id' => 'product-uuid-456',
            'quantity' => 3,
            'unit_price' => 12.99,
            'total_price' => 38.97,
        ]);

        $this->assertEquals('order-uuid-123', $item->order_id);
        $this->assertEquals('product-uuid-456', $item->product_id);
        $this->assertEquals(3, $item->quantity);
        $this->assertEquals(12.99, $item->unit_price);
        $this->assertEquals(38.97, $item->total_price);
    }

    public function test_quantity_cast_to_integer(): void
    {
        $item = new MartOrderItem([
            'quantity' => '5',
        ]);

        $this->assertIsInt($item->quantity);
        $this->assertEquals(5, $item->quantity);
    }

    public function test_unit_price_cast_to_decimal(): void
    {
        $item = new MartOrderItem([
            'unit_price' => '15.99',
        ]);

        $this->assertIsString($item->unit_price);
        $this->assertEquals('15.99', $item->unit_price);
    }

    public function test_total_price_cast_to_decimal(): void
    {
        $item = new MartOrderItem([
            'total_price' => '47.97',
        ]);

        $this->assertIsString($item->total_price);
        $this->assertEquals('47.97', $item->total_price);
    }

    public function test_zero_quantity(): void
    {
        $item = new MartOrderItem([
            'quantity' => 0,
            'unit_price' => 10.00,
            'total_price' => 0.00,
        ]);

        $this->assertEquals(0, $item->quantity);
        $this->assertEquals(0.00, $item->total_price);
    }

    public function test_negative_total_price(): void
    {
        $item = new MartOrderItem([
            'total_price' => -5.00,
        ]);

        $this->assertEquals('-5.00', $item->total_price);
    }

    public function test_order_method_exists(): void
    {
        $item = new MartOrderItem();
        $this->assertTrue(method_exists($item, 'order'));
    }

    public function test_product_method_exists(): void
    {
        $item = new MartOrderItem();
        $this->assertTrue(method_exists($item, 'product'));
    }

    public function test_uses_has_uuids_trait(): void
    {
        $traits = class_uses(MartOrderItem::class);

        $this->assertContains(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits);
    }

    public function test_multiple_items_for_same_order(): void
    {
        $items = [
            new MartOrderItem([
                'order_id' => 'same-order-123',
                'product_id' => 'product-a',
                'quantity' => 2,
                'unit_price' => 10.00,
                'total_price' => 20.00,
            ]),
            new MartOrderItem([
                'order_id' => 'same-order-123',
                'product_id' => 'product-b',
                'quantity' => 1,
                'unit_price' => 15.00,
                'total_price' => 15.00,
            ]),
        ];

        $this->assertEquals('same-order-123', $items[0]->order_id);
        $this->assertEquals('same-order-123', $items[1]->order_id);
        $this->assertNotEquals($items[0]->product_id, $items[1]->product_id);
    }

    public function test_high_precision_prices(): void
    {
        $item = new MartOrderItem([
            'unit_price' => 0.99,
            'total_price' => 99.99,
        ]);

        $this->assertEquals(0.99, (float) $item->unit_price);
        $this->assertEquals(99.99, (float) $item->total_price);
    }
}
