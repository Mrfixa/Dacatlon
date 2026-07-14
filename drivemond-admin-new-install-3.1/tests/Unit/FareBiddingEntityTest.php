<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\FareBidding;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FareBidding entity.
 */
class FareBiddingEntityTest extends TestCase
{
    public function test_fillable_attributes_exist(): void
    {
        $fareBidding = new FareBidding();
        $fillable = $fareBidding->getFillable();

        // Core fields
        $this->assertContains('trip_request_id', $fillable);
        $this->assertContains('driver_id', $fillable);
        $this->assertContains('customer_id', $fillable);
        $this->assertContains('bid_fare', $fillable);
        $this->assertContains('is_ignored', $fillable);
    }

    public function test_casts_are_defined(): void
    {
        $fareBidding = new FareBidding();
        $casts = $fareBidding->getCasts();

        // bid_fare should be cast to string
        $this->assertArrayHasKey('bid_fare', $casts);
        $this->assertEquals('string', $casts['bid_fare']);

        // is_ignored should be cast to boolean
        $this->assertArrayHasKey('is_ignored', $casts);
        $this->assertEquals('boolean', $casts['is_ignored']);
    }

    public function test_model_uses_uuid_trait(): void
    {
        $fareBidding = new FareBidding();
        $traits = class_uses_recursive($fareBidding);

        $this->assertContains('App\Traits\HasUuid', $traits);
    }

    public function test_model_uses_factory_trait(): void
    {
        $fareBidding = new FareBidding();
        $traits = class_uses_recursive($fareBidding);

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
    }

    public function test_relationships_are_defined(): void
    {
        $fareBidding = new FareBidding();

        // Test that relationship methods exist
        $this->assertTrue(method_exists($fareBidding, 'trip_request'));
        $this->assertTrue(method_exists($fareBidding, 'customer'));
        $this->assertTrue(method_exists($fareBidding, 'driver'));
        $this->assertTrue(method_exists($fareBidding, 'driver_last_location'));
        $this->assertTrue(method_exists($fareBidding, 'customerReceivedReviews'));
        $this->assertTrue(method_exists($fareBidding, 'driverReceivedReviews'));
    }

    public function test_scope_of_is_not_ignored_exists(): void
    {
        $fareBidding = new FareBidding();

        // Scope method should exist (prefixed with 'scope')
        $this->assertTrue(method_exists($fareBidding, 'scopeOfIsNotIgnored'));
    }

    public function test_new_factory_method_exists(): void
    {
        $fareBidding = new FareBidding();

        $this->assertTrue(method_exists($fareBidding, 'newFactory'));
    }
}
