<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartProduct;
use PHPUnit\Framework\TestCase;

class MartProductTest extends TestCase
{
    public function test_effective_price_returns_discount_price_when_lower(): void
    {
        $product = new MartProduct([
            'price' => 100.00,
            'discount_price' => 75.00,
        ]);

        $this->assertEquals(75.0, $product->effective_price);
    }

    public function test_effective_price_returns_original_price_when_no_discount(): void
    {
        $product = new MartProduct([
            'price' => 100.00,
            'discount_price' => null,
        ]);

        $this->assertEquals(100.0, $product->effective_price);
    }

    public function test_effective_price_returns_original_price_when_discount_higher(): void
    {
        $product = new MartProduct([
            'price' => 100.00,
            'discount_price' => 150.00,
        ]);

        $this->assertEquals(100.0, $product->effective_price);
    }

    public function test_effective_price_returns_original_price_when_discount_equals_price(): void
    {
        $product = new MartProduct([
            'price' => 100.00,
            'discount_price' => 100.00,
        ]);

        $this->assertEquals(100.0, $product->effective_price);
    }

    public function test_effective_price_returns_original_price_when_discount_is_zero(): void
    {
        $product = new MartProduct([
            'price' => 100.00,
            'discount_price' => 0,
        ]);

        $this->assertEquals(100.0, $product->effective_price);
    }

    public function test_effective_price_handles_string_prices(): void
    {
        $product = new MartProduct([
            'price' => '50.00',
            'discount_price' => '25.00',
        ]);

        $this->assertEquals(25.0, $product->effective_price);
    }

    public function test_effective_price_handles_integer_prices(): void
    {
        $product = new MartProduct([
            'price' => 50,
            'discount_price' => 25,
        ]);

        $this->assertEquals(25.0, $product->effective_price);
    }

    public function test_product_fillable_attributes(): void
    {
        $product = new MartProduct([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'discount_price' => 79.99,
            'unit' => 'pcs',
            'image' => 'test.jpg',
            'category' => 'Electronics',
            'is_active' => true,
            'is_featured' => true,
            'is_popular' => false,
            'sold_count' => 100,
            'stock' => 50,
            'zone_id' => 'test-zone-id',
        ]);

        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('Test Description', $product->description);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals(79.99, $product->discount_price);
        $this->assertEquals('pcs', $product->unit);
        $this->assertEquals('test.jpg', $product->image);
        $this->assertEquals('Electronics', $product->category);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->is_featured);
        $this->assertFalse($product->is_popular);
        $this->assertEquals(100, $product->sold_count);
        $this->assertEquals(50, $product->stock);
    }

    public function test_product_casts_attributes_correctly(): void
    {
        $product = new MartProduct([
            'price' => '99.99',
            'discount_price' => '79.99',
            'is_active' => 1,
            'is_featured' => 0,
            'is_popular' => '1',
            'sold_count' => '50',
            'stock' => '100',
        ]);

        $this->assertIsString($product->price);
        $this->assertIsString($product->discount_price);
        $this->assertTrue($product->is_active);
        $this->assertFalse($product->is_featured);
        $this->assertTrue($product->is_popular);
        $this->assertEquals(50, $product->sold_count);
        $this->assertEquals(100, $product->stock);
    }
}
