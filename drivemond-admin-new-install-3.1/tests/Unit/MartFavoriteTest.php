<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartFavorite;
use PHPUnit\Framework\TestCase;

class MartFavoriteTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $favorite = new MartFavorite([
            'customer_id' => 'customer-uuid-123',
            'product_id' => 'product-uuid-456',
        ]);

        $this->assertEquals('customer-uuid-123', $favorite->customer_id);
        $this->assertEquals('product-uuid-456', $favorite->product_id);
    }

    public function test_product_method_exists(): void
    {
        $favorite = new MartFavorite();
        $this->assertTrue(method_exists($favorite, 'product'));
    }

    public function test_uses_has_uuids_trait(): void
    {
        $traits = class_uses(MartFavorite::class);

        $this->assertContains(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits);
    }

    public function test_multiple_favorites_same_customer(): void
    {
        $favorites = [
            new MartFavorite(['customer_id' => 'same-customer', 'product_id' => 'product-a']),
            new MartFavorite(['customer_id' => 'same-customer', 'product_id' => 'product-b']),
            new MartFavorite(['customer_id' => 'same-customer', 'product_id' => 'product-c']),
        ];

        $this->assertCount(3, $favorites);
        foreach ($favorites as $fav) {
            $this->assertEquals('same-customer', $fav->customer_id);
        }
    }

    public function test_multiple_customers_same_product(): void
    {
        $favorites = [
            new MartFavorite(['customer_id' => 'customer-a', 'product_id' => 'same-product']),
            new MartFavorite(['customer_id' => 'customer-b', 'product_id' => 'same-product']),
        ];

        foreach ($favorites as $fav) {
            $this->assertEquals('same-product', $fav->product_id);
        }
        $this->assertNotEquals($favorites[0]->customer_id, $favorites[1]->customer_id);
    }

    public function test_uuid_format(): void
    {
        $favorite = new MartFavorite([
            'customer_id' => '550e8400-e29b-41d4-a716-446655440000',
            'product_id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $favorite->customer_id
        );
    }
}
