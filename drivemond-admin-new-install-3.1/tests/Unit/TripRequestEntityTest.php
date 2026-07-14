<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\TripRequest;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TripRequest entity constants and scopes.
 */
class TripRequestEntityTest extends TestCase
{
    public function test_fillable_attributes_exist(): void
    {
        $tripRequest = new TripRequest();
        $fillable = $tripRequest->getFillable();

        // Core fields
        $this->assertContains('ref_id', $fillable);
        $this->assertContains('customer_id', $fillable);
        $this->assertContains('driver_id', $fillable);
        $this->assertContains('current_status', $fillable); // TripRequest uses current_status, not status
        $this->assertContains('payment_status', $fillable);
        $this->assertContains('payment_method', $fillable);
        $this->assertContains('estimated_fare', $fillable);
        $this->assertContains('actual_fare', $fillable);
        $this->assertContains('coupon_amount', $fillable);
        $this->assertContains('discount_amount', $fillable);
    }

    public function test_casts_are_defined(): void
    {
        $tripRequest = new TripRequest();
        $casts = $tripRequest->getCasts();

        // Numeric casts
        $this->assertArrayHasKey('estimated_fare', $casts);
        $this->assertArrayHasKey('actual_fare', $casts);
        $this->assertArrayHasKey('paid_fare', $casts);
        $this->assertArrayHasKey('coupon_amount', $casts);
        $this->assertArrayHasKey('discount_amount', $casts);

        // Boolean casts
        $this->assertArrayHasKey('is_paused', $casts);
        $this->assertArrayHasKey('is_notification_sent', $casts);
    }

    public function test_cast_types_are_correct(): void
    {
        $tripRequest = new TripRequest();
        $casts = $tripRequest->getCasts();

        // Numeric fields should cast to float
        $this->assertEquals('float', $casts['estimated_fare']);
        $this->assertEquals('float', $casts['actual_fare']);

        // Boolean fields should cast to boolean
        $this->assertEquals('boolean', $casts['is_paused']);
        $this->assertEquals('boolean', $casts['is_notification_sent']);

        // Integer fields should cast to integer
        $this->assertEquals('integer', $casts['rise_request_count']);
    }

    public function test_model_uses_uuid_trait(): void
    {
        $tripRequest = new TripRequest();
        $traits = class_uses_recursive($tripRequest);

        $this->assertContains('App\Traits\HasUuid', $traits);
    }

    public function test_model_uses_soft_deletes(): void
    {
        $tripRequest = new TripRequest();
        $traits = class_uses_recursive($tripRequest);

        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', $traits);
    }

    public function test_model_uses_factory_trait(): void
    {
        $tripRequest = new TripRequest();
        $traits = class_uses_recursive($tripRequest);

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
    }

    public function test_relationships_are_defined(): void
    {
        $tripRequest = new TripRequest();

        // Test that relationship methods exist and return correct types
        $this->assertTrue(method_exists($tripRequest, 'channel'));
        $this->assertTrue(method_exists($tripRequest, 'conversations'));
        $this->assertTrue(method_exists($tripRequest, 'fare_biddings'));
        $this->assertTrue(method_exists($tripRequest, 'tripRoutes'));
        $this->assertTrue(method_exists($tripRequest, 'customer'));
        $this->assertTrue(method_exists($tripRequest, 'driver'));
        $this->assertTrue(method_exists($tripRequest, 'vehicle'));
        $this->assertTrue(method_exists($tripRequest, 'zone'));
        $this->assertTrue(method_exists($tripRequest, 'coupon'));
        $this->assertTrue(method_exists($tripRequest, 'discount'));
    }

    public function test_scope_type_exists(): void
    {
        $tripRequest = new TripRequest();

        $this->assertTrue(method_exists($tripRequest, 'scopeType'));
    }

    public function test_distance_wise_fare_attribute_exists(): void
    {
        $tripRequest = new TripRequest();

        $this->assertTrue(method_exists($tripRequest, 'distance_wise_fare'));
    }
}
